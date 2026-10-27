<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceAuthorization;
use App\Services\ApiTokenService;
use App\Services\DeviceAuthorizationService as Devices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Browser sign-in for desktop clients. See DeviceAuthorizationService.
 */
class DeviceAuthController extends Controller
{
    public function start(Request $request, Devices $devices): JsonResponse
    {
        $data = $request->validate([
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        [$authorization, $deviceCode] = $devices->start($data['device_name'], $request->ip());

        $userCode = $authorization->formattedUserCode();

        return response()->json([
            'device_code'               => $deviceCode,
            'user_code'                 => $userCode,
            'verification_uri'          => route('device.show'),
            'verification_uri_complete' => route('device.show', ['codigo' => $userCode]),
            'expires_in'                => Devices::EXPIRES_IN_SECONDS,
            'interval'                  => Devices::INTERVAL_SECONDS,
        ]);
    }

    public function token(Request $request, Devices $devices, ApiTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'device_code' => ['required', 'string', 'max:128'],
        ]);

        [$result, $authorization] = $devices->poll($data['device_code']);

        return match ($result) {
            Devices::PENDING => $this->error(
                'Aguardando a autorização no navegador.', Devices::PENDING, 400,
            ),
            Devices::SLOW_DOWN => $this->error(
                'Consultas rápidas demais. Aguarde alguns segundos.', Devices::SLOW_DOWN, 429,
                ['Retry-After' => Devices::INTERVAL_SECONDS],
            ),
            Devices::DENIED => $this->error(
                'O acesso foi negado no navegador.', Devices::DENIED, 403,
            ),
            Devices::EXPIRED => $this->error(
                'Este código expirou ou já foi usado. Comece o login novamente.', Devices::EXPIRED, 410,
            ),
            default => $this->approved($authorization, $request, $tokens),
        };
    }

    private function approved(DeviceAuthorization $authorization, Request $request, ApiTokenService $tokens): JsonResponse
    {
        $user = $authorization->user;

        // The account may have been deactivated between the approval and this poll.
        if (! $user || ! $user->is_active) {
            return $this->error('Sua conta está desativada. Entre em contato com o suporte.', 'account_inactive', 403);
        }

        return response()->json($tokens->issue($user, $authorization->device_name, $request));
    }

    private function error(string $message, string $code, int $status, array $headers = []): JsonResponse
    {
        return response()->json(['message' => $message, 'code' => $code], $status, $headers);
    }
}
