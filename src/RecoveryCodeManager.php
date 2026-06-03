<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Recovery;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Padosoft\Rebel\Core\Audit\AuditEvent;
use Padosoft\Rebel\Core\Audit\AuthEventType;
use Padosoft\Rebel\Core\Contracts\AuditLogger;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Recovery\Models\RebelRecoveryCode;
use Psr\Clock\ClockInterface;

/**
 * Issues and verifies single-use recovery (backup) codes.
 *
 * Codes are returned in plaintext ONCE at generation and stored only as a keyed HMAC of
 * (salt|code) with a `key_version` (so the pepper can rotate). Verification is atomic and
 * single-use (row lock), and regenerating invalidates all previous unconsumed codes.
 */
final class RecoveryCodeManager
{
    public function __construct(
        private readonly KeyedHasher $hasher,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
        private readonly RecoveryCodeGenerator $generator,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * Generate a fresh set of recovery codes (invalidating any previous unconsumed ones).
     * Returns the plaintext codes — show/download them once; they cannot be retrieved again.
     *
     * @return list<string>
     */
    public function generate(Authenticatable $user, int $count = 10): array
    {
        $codes = $this->generator->generate($count);

        $this->db->connection()->transaction(function () use ($user, $codes): void {
            RebelRecoveryCode::query()
                ->where('subject_type', $user::class)
                ->where('subject_id', $this->subjectId($user))
                ->whereNull('consumed_at')
                ->delete();

            foreach ($codes as $code) {
                $salt = bin2hex(random_bytes(16));
                $hashed = $this->hasher->hash($salt.'|'.$this->canonical($code));

                $record = new RebelRecoveryCode;
                $record->fill([
                    'subject_type' => $user::class,
                    'subject_id' => $this->subjectId($user),
                    'code_hmac' => $hashed->hash,
                    'salt' => $salt,
                    'key_version' => $hashed->keyVersion,
                ]);
                $record->save();
            }
        });

        $this->record('recovery.codes.generated', $user, ['count' => $count]);

        return $codes;
    }

    /** Verify a recovery code and consume it (single-use). Returns true on success. */
    public function verify(Authenticatable $user, string $code): bool
    {
        return $this->db->connection()->transaction(function () use ($user, $code): bool {
            $records = RebelRecoveryCode::query()
                ->where('subject_type', $user::class)
                ->where('subject_id', $this->subjectId($user))
                ->whereNull('consumed_at')
                ->lockForUpdate()
                ->get();

            $canonical = $this->canonical($code);
            $matched = null;

            // Iterate ALL records (no early return) so the response time doesn't leak the
            // position of the matching code; matches() compares in constant time internally.
            foreach ($records as $record) {
                if ($this->hasher->matches($record->salt.'|'.$canonical, $record->code_hmac, $record->key_version)) {
                    $matched ??= $record;
                }
            }

            if ($matched !== null) {
                $matched->consumed_at = CarbonImmutable::instance($this->clock->now());
                $matched->save();

                $this->record(AuthEventType::RecoveryCompleted->value, $user, []);

                return true;
            }

            $this->record('recovery.failed', $user, ['reason' => 'no_match']);

            return false;
        });
    }

    /** How many unconsumed recovery codes the subject still has. */
    public function remaining(Authenticatable $user): int
    {
        return RebelRecoveryCode::query()
            ->where('subject_type', $user::class)
            ->where('subject_id', $this->subjectId($user))
            ->whereNull('consumed_at')
            ->count();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function record(string $type, Authenticatable $user, array $metadata): void
    {
        $this->audit->record(new AuditEvent(
            type: $type,
            subjectType: $user::class,
            subjectId: $this->subjectId($user),
            metadata: $metadata,
        ));
    }

    /**
     * Normalize a code for hashing/verifying: uppercase, drop separators, and forgive the
     * common Crockford typos (O→0, I/L→1) so a correctly-typed code never fails on format.
     */
    private function canonical(string $code): string
    {
        $mapped = strtr(strtoupper($code), ['O' => '0', 'I' => '1', 'L' => '1']);

        return preg_replace('/[^0-9A-Z]/', '', $mapped) ?? '';
    }

    private function subjectId(Authenticatable $user): string
    {
        $id = $user->getAuthIdentifier();

        // Fail-fast: a non-scalar identifier would collapse multiple users onto the same
        // subject bucket ('') and let them consume each other's recovery codes.
        if (! is_scalar($id)) {
            throw new \UnexpectedValueException('Recovery codes require a scalar subject identifier.');
        }

        return (string) $id;
    }
}
