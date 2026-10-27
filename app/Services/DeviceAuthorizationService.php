<?php

namespace App\Services;

use App\Models\DeviceAuthorization;
use App\Models\User;

/**
 * Browser sign-in for desktop clients (OAuth 2.0 device authorization flow,
 * RFC 8628). The client starts a request and shows a short code, the user
 * approves it on the site while signed in (password or Google), and the
 * client, polling with its secret device code, receives an API token.
 * The password never passes through the client.
 */
class DeviceAuthorizationService
{
    public const EXPIRES_IN_SECONDS = 600;

    /** Minimum seconds between two polls of the same request. */
    public const INTERVAL_SECONDS = 5;

    /**
     * Consonants only (RFC 8628 §6.1): no vowels, so no words, and no
     * look-alikes such as 0/O or 1/I. 20^8 combinations.
     */
    private const USER_CODE_ALPHABET = 'BCDFGHJKLMNPQRSTVWXZ';
    private const USER_CODE_LENGTH   = 8;

    // Poll results.
    public const PENDING   = 'authorization_pending';
    public const SLOW_DOWN = 'slow_down';
    public const DENIED    = 'access_denied';
    public const EXPIRED   = 'expired_token';
    public const APPROVED  = 'approved';

    /**
     * @return array{0: DeviceAuthorization, 1: string}  the request and the plain device code
     */
    public function start(string $deviceName, ?string $ip): array
    {
        // No scheduler on the hosting: old requests are cleaned up here.
        DeviceAuthorization::where('expires_at', '<', now()->subDay())->delete();

        $deviceCode = bin2hex(random_bytes(32));

        $authorization = DeviceAuthorization::create([
            'device_code_hash' => hash('sha256', $deviceCode),
            'user_code'        => $this->newUserCode(),
            'device_name'      => $deviceName,
            'ip_address'       => $ip,
            'expires_at'       => now()->addSeconds(self::EXPIRES_IN_SECONDS),
        ]);

        return [$authorization, $deviceCode];
    }

    /**
     * Accepts what the user typed ("kxqp-wmzb", "KXQP WMZB"...).
     */
    public function findPending(?string $userCode): ?DeviceAuthorization
    {
        $code = $this->normalizeUserCode($userCode);

        if (strlen($code) !== self::USER_CODE_LENGTH) {
            return null;
        }

        return DeviceAuthorization::pending()->where('user_code', $code)->first();
    }

    /**
     * @return bool  false if the request was answered or expired in the meantime
     */
    public function approve(DeviceAuthorization $authorization, User $user): bool
    {
        return $this->answer($authorization, $user, 'approved_at');
    }

    public function deny(DeviceAuthorization $authorization, User $user): bool
    {
        return $this->answer($authorization, $user, 'denied_at');
    }

    /**
     * Called by the client every few seconds with its secret device code.
     *
     * @return array{0: string, 1: DeviceAuthorization|null}  one of the poll results above
     */
    public function poll(string $deviceCode): array
    {
        $authorization = DeviceAuthorization::where('device_code_hash', hash('sha256', $deviceCode))->first();

        // Unknown, expired and already used codes look the same to the client.
        if (! $authorization || $authorization->consumed_at || $authorization->isExpired()) {
            return [self::EXPIRED, null];
        }

        if ($authorization->denied_at) {
            return [self::DENIED, $authorization];
        }

        if ($authorization->approved_at) {
            // Atomic: two parallel polls can never both receive a token.
            $consumed = DeviceAuthorization::whereKey($authorization->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return $consumed === 1 ? [self::APPROVED, $authorization] : [self::EXPIRED, null];
        }

        // One second of tolerance for timer jitter on the client.
        $tooSoon = $authorization->last_polled_at
            && $authorization->last_polled_at->gt(now()->subSeconds(self::INTERVAL_SECONDS - 1));

        $authorization->forceFill(['last_polled_at' => now()])->save();

        return [$tooSoon ? self::SLOW_DOWN : self::PENDING, $authorization];
    }

    private function answer(DeviceAuthorization $authorization, User $user, string $column): bool
    {
        // Atomic: only the first answer counts, and never after expiration.
        return DeviceAuthorization::whereKey($authorization->id)
            ->pending()
            ->update(['user_id' => $user->id, $column => now()]) === 1;
    }

    private function newUserCode(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < self::USER_CODE_LENGTH; $i++) {
                $code .= self::USER_CODE_ALPHABET[random_int(0, strlen(self::USER_CODE_ALPHABET) - 1)];
            }
        } while (DeviceAuthorization::where('user_code', $code)->exists());

        return $code;
    }

    private function normalizeUserCode(?string $input): string
    {
        return preg_replace('/[^A-Z]/', '', strtoupper((string) $input));
    }
}
