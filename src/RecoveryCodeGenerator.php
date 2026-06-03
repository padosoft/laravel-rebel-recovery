<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Recovery;

/**
 * Generates human-friendly, high-entropy recovery codes using a CSPRNG and a
 * Crockford-style alphabet (no ambiguous I/L/O/U). 20 symbols ≈ 100 bits of entropy,
 * grouped as XXXX-XXXX-XXXX-XXXX-XXXX.
 */
final class RecoveryCodeGenerator
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const LENGTH = 20;

    /**
     * @return list<string>
     */
    public function generate(int $count): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->one();
        }

        return $codes;
    }

    private function one(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $raw = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $raw .= self::ALPHABET[random_int(0, $max)];
        }

        return implode('-', str_split($raw, 4));
    }
}
