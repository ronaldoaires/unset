<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PluginReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PluginReleaseController extends Controller
{
    /**
     * Latest plugin release. Public on purpose: an old or signed-out plugin
     * must still be able to find out that it has to update.
     */
    public function __invoke(Request $request, PluginReleaseService $releases): JsonResponse
    {
        $data = $request->validate([
            'current' => ['nullable', 'string', 'regex:/^[vV]?\d+(\.\d+){0,3}$/'],
        ]);

        $release = $releases->latest();

        if (! $release) {
            return response()->json([
                'message' => 'Não foi possível verificar atualizações agora. Tente novamente mais tarde.',
                'code'    => 'update_info_unavailable',
            ], 503, ['Retry-After' => 60]);
        }

        $current    = isset($data['current']) ? $releases->normalize($data['current']) : null;
        $minVersion = $releases->minVersion();

        return response()->json([
            'data' => [
                ...$release,
                'min_version'      => $minVersion,
                'current'          => $current,
                'update_available' => $current !== null ? version_compare($current, $release['version'], '<') : null,
                'update_required'  => $current !== null ? version_compare($current, $minVersion, '<') : null,
            ],
        ]);
    }
}
