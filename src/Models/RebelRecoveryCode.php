<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Recovery\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Padosoft\Rebel\Core\Concerns\BelongsToTenant;

/**
 * A single-use recovery (backup) code, stored only as a keyed HMAC of (salt|code).
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $subject_type
 * @property string $subject_id
 * @property string $code_hmac
 * @property string $salt
 * @property int $key_version
 * @property CarbonImmutable|null $consumed_at
 */
final class RebelRecoveryCode extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'rebel_recovery_codes';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id', 'subject_type', 'subject_id', 'code_hmac', 'salt', 'key_version', 'consumed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key_version' => 'integer',
            'consumed_at' => 'immutable_datetime',
        ];
    }
}
