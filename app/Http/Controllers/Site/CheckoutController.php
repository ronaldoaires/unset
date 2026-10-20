<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Rules\Cpf;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Closure;
use App\Models\Subscription;
use App\Services\MercadoPagoService;
use App\Services\SubscriptionSyncService;
use Throwable;

class CheckoutController extends Controller
{
    public function show(Request $request, Plan $plan): View|RedirectResponse
    {
        $user = $request->user();

        if ($redirect = $this->blocker($user, $plan)) {
            return $redirect;
        }

        return view('site.checkout.show', [
            'plan'       => $plan,
            'cycle'      => $this->cycle($request, $plan),
            'canYearly'  => $plan->price_yearly > 0,
            'cpf'        => Cpf::format($user->cpf),
            'phone'      => $user->phone,
            'needsTerms' => $user->terms_version !== config('legal.version'),
        ]);
    }

    public function store(Request $request, Plan $plan, MercadoPagoService $mp): RedirectResponse
    {
        $user = $request->user();

        if ($redirect = $this->blocker($user, $plan)) {
            return $redirect;
        }

        // Validate digits only; the mask characters are added back on save.
        $request->merge([
            'cpf'   => preg_replace('/\D/', '', (string) $request->input('cpf')),
            'phone' => preg_replace('/\D/', '', (string) $request->input('phone')),
        ]);

        // Already accepted the current version: no need to ask again.
        $needsTerms = $user->terms_version !== config('legal.version');

        $rules = [
            'cpf' => [
                'required',
                new Cpf,
                // Uniqueness is checked on the hash because the stored CPF is encrypted.
                function (string $attribute, mixed $value, Closure $fail) use ($user) {
                    $taken = User::where('cpf_hash', User::hashCpf($value))
                        ->whereKeyNot($user->id)
                        ->exists();

                    if ($taken) {
                        $fail('Este CPF já está em uso por outra conta.');
                    }
                },
            ],
            'phone' => ['required', 'digits_between:10,11'],
        ];

        if ($needsTerms) {
            $rules['terms'] = ['accepted'];
        }

        $data = $request->validate($rules, [
            'cpf.required'         => 'Informe seu CPF.',
            'phone.required'       => 'Informe seu telefone.',
            'phone.digits_between' => 'Informe um telefone com DDD (10 ou 11 dígitos).',
            'terms.accepted'       => 'Para continuar, aceite os Termos de Uso e a Política de Privacidade.',
        ]);

        $attributes = [
            'cpf'   => $data['cpf'],
            'phone' => Phone::format($data['phone']),
        ];

        // Proof of acceptance: when, and which version of the documents.
        if ($needsTerms) {
            $attributes['terms_accepted_at'] = now();
            $attributes['terms_version']     = config('legal.version');
        }

        $user->forceFill($attributes)->save();

        $cycle = $this->cycle($request, $plan);

        // Abandoned attempts are canceled at Mercado Pago, so they can never be authorized later.
        $user->subscriptions()->where('status', 'pending')->get()->each(function (Subscription $old) use ($mp) {
            if ($old->mp_preapproval_id) {
                try {
                    $mp->cancelPreapproval($old->mp_preapproval_id);
                } catch (Throwable $e) {
                    report($e);
                }
            }

            $old->delete();
        });

        $subscription = Subscription::create([
            'user_id'       => $user->id,
            'plan_id'       => $plan->id,
            'billing_cycle' => $cycle === 'anual' ? 'yearly' : 'monthly',
            'status'        => 'pending',
            'started_at'    => now(),
        ]);

        $subscription->update(['mp_external_reference' => (string) $subscription->id]);

        try {
            $preapproval = $mp->createSubscription($subscription, $user, $plan);
        } catch (Throwable $e) {
            report($e);
            $subscription->delete();

            return back()->withInput()->withErrors([
                'payment' => 'Não foi possível iniciar o pagamento agora. Tente novamente em instantes.',
            ]);
        }

        $subscription->update(['mp_preapproval_id' => $preapproval['id']]);

        return redirect()->away($preapproval['init_point']);
    }

    /**
     * Where this user must go instead of the checkout, or null when they can proceed.
     */
    private function blocker(User $user, Plan $plan): ?RedirectResponse
    {
        abort_unless($plan->is_active && ! $plan->isFree(), 404);

        if ($user->delete_requested_at) {
            return redirect()->route('account.danger')
                ->with('status', 'Você pediu o encerramento da conta. Cancele o pedido para poder assinar.');
        }

        $hasLiveSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'trial', 'past_due'])
            ->exists();

        if ($hasLiveSubscription || $user->isPremium()) {
            return redirect()->route('account.subscription')
                ->with('status', 'Você já tem uma assinatura em andamento.');
        }

        return null;
    }

    /**
     * "anual" only when the plan has a yearly price; anything else falls back to "mensal".
     */
    private function cycle(Request $request, Plan $plan): string
    {
        return $request->input('ciclo') === 'anual' && $plan->price_yearly > 0
            ? 'anual'
            : 'mensal';
    }

    /**
     * Where Mercado Pago sends the customer back after the payment screen.
     * Shows the real status, which is read from Mercado Pago right now because
     * the webhook may arrive later (or not at all in a local environment).
     */
    public function finish(Request $request, MercadoPagoService $mp, SubscriptionSyncService $sync): View|RedirectResponse
    {
        $subscription = $request->user()->subscriptions()->with('plan')->latest('id')->first();

        if (! $subscription) {
            return redirect()->route('home');
        }

        if ($subscription->mp_preapproval_id) {
            try {
                $sync->syncPreapproval($mp->getPreapproval($subscription->mp_preapproval_id));
                $subscription->refresh();
            } catch (Throwable $e) {
                report($e);
            }
        }

        return view('site.checkout.finish', ['subscription' => $subscription]);
    }
}
