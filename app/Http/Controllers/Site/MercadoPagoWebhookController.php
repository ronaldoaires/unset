<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoService;
use App\Services\SubscriptionSyncService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, MercadoPagoService $mp, SubscriptionSyncService $sync): Response
    {
        abort_unless($mp->hasValidSignature($request), 401);

        $id   = $mp->notificationId($request);
        $type = $request->input('type') ?? $request->query('type');

        if ($id === null) {
            return response('ok');
        }

        try {
            match ($type) {
                'subscription_preapproval'        => $sync->syncPreapproval($mp->getPreapproval($id)),
                'subscription_authorized_payment' => $sync->syncAuthorizedPayment($mp->getAuthorizedPayment($id)),
                default                           => null,
            };
        } catch (RequestException $e) {
            // The panel's "simulate notification" sends ids that do not exist: nothing to retry.
            if ($e->response->status() === 404) {
                Log::info("Mercado Pago webhook: {$type} {$id} not found.");

                return response('ok');
            }

            // Any other failure answers 500, so Mercado Pago retries later.
            throw $e;
        }

        return response('ok');
    }
}