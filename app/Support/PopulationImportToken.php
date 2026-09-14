<?php

namespace App\Support;

use App\Exceptions\PopulationImportException;
use Illuminate\Support\Facades\Crypt;

class PopulationImportToken
{
    private const LIFETIME_SECONDS = 900;

    public function issue(int $userId, string $fileHash, string $fingerprint, ?string $hamletOverride): string
    {
        return Crypt::encryptString(json_encode([
            'user_id' => $userId,
            'file_hash' => $fileHash,
            'fingerprint' => $fingerprint,
            'hamlet_override' => $hamletOverride,
            'expires_at' => now()->addSeconds(self::LIFETIME_SECONDS)->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    /** @return array{user_id: int, file_hash: string, fingerprint: string, hamlet_override: ?string, expires_at: int} */
    public function decode(string $token, int $userId): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new PopulationImportException('Pratinjau import tidak valid. Silakan lakukan pratinjau ulang.', previous: $exception);
        }

        if (! is_array($payload)
            || (int) ($payload['user_id'] ?? 0) !== $userId
            || ! is_string($payload['file_hash'] ?? null)
            || ! is_string($payload['fingerprint'] ?? null)) {
            throw new PopulationImportException('Pratinjau import tidak cocok dengan akun ini. Silakan lakukan pratinjau ulang.');
        }

        if ((int) ($payload['expires_at'] ?? 0) < now()->timestamp) {
            throw new PopulationImportException('Pratinjau import sudah kedaluwarsa. Silakan lakukan pratinjau ulang.');
        }

        return [
            'user_id' => (int) $payload['user_id'],
            'file_hash' => $payload['file_hash'],
            'fingerprint' => $payload['fingerprint'],
            'hamlet_override' => isset($payload['hamlet_override']) ? (string) $payload['hamlet_override'] : null,
            'expires_at' => (int) $payload['expires_at'],
        ];
    }
}
