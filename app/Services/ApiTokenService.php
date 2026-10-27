<?php

namespace App\Services;

use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Issues API tokens. Shared by the password login and the browser sign-in,
 * so both apply the same device rules and answer with the same payload.
 */
class ApiTokenService
{
    /** Devices (tokens) a user may keep signed in at the same time. */
    public const MAX_DEVICES = 5;

    public function __construct(
        private LoginLogService $logs,
        private AccessService $access,
    ) {}

    /**
     * Creates the token, records the successful login and returns the
     * login payload (token, expiration, user and access block).
     *
     * @return array<string, mixed>
     */
    public function issue(User $user, string $deviceName, Request $request): array
    {
        $this->pruneDevices($user, $deviceName);

        $minutes   = config('sanctum.expiration');
        $expiresAt = $minutes ? now()->addMinutes((int) $minutes) : null;
        $newToken  = $user->createToken($deviceName, ['*'], $expiresAt);

        $this->logs->record($request, $user, true);

        return [
            'token'      => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toAtomString(),
            'user'       => (new UserResource($user))->resolve(),
            'access'     => $this->access->summary($user),
        ];
    }

    /**
     * A device signing in again replaces its previous token, and the oldest
     * tokens are dropped when the user is over the device limit.
     */
    private function pruneDevices(User $user, string $deviceName): void
    {
        $user->tokens()->where('name', $deviceName)->delete();

        $excess = $user->tokens()->count() - (self::MAX_DEVICES - 1);

        if ($excess > 0) {
            $user->tokens()->oldest()->limit($excess)->get()->each->delete();
        }
    }
}
