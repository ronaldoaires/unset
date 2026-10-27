<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [ForceJsonResponse::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );

        // Policy denials that carry a code (Response::deny($message, $code)) keep it
        // in API answers, so clients branch on the code instead of the message.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            $previous = $e->getPrevious();

            if (! $request->is('api/*') || ! $previous instanceof AuthorizationException) {
                return null;
            }

            $code = $previous->response()?->code() ?? $previous->getCode();

            if (blank($code)) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage(),
                'code'    => $code,
            ], 403);
        });

        // Expired or tampered verification links get a friendly page instead of
        // a bare 403. Returning null for any other route keeps the default handling.
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            if ($request->routeIs('verification.verify')) {
                return response()->view('auth.verification-failed', [], 403);
            }

            if ($request->routeIs('email-change.*')) {
                return response()->view('auth.email-change-result', [
                    'ok'      => false,
                    'title'   => 'Link inválido ou expirado',
                    'message' => 'Este link não é mais válido. Faça um novo pedido de troca de e-mail na sua conta.',
                ], 403);
            }
        });
    })->create();
