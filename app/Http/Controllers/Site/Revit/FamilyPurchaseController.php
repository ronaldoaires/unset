<?php

namespace App\Http\Controllers\Site\Revit;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyPurchase;
use App\Models\User;
use App\Rules\Cpf;
use App\Services\MercadoPagoService;
use App\Services\PurchaseSyncService;
use App\Support\Phone;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class FamilyPurchaseController extends Controller
{
    public function show(Request $request, Family $family): View|RedirectResponse
    {
        $user = $request->user();

        if ($redirect = $this->blocker($user, $family)) {
            return $redirect;
        }

        // Lazy loading is blocked outside production.
        $family->loadMissing('primaryImage');

        return view('site.revit.families.purchase', [
            'family'     => $family,
            'cpf'        => Cpf::format($user->cpf),
            'phone'      => $user->phone,
            'needsTerms' => $user->terms_version !== config('legal.version'),
        ]);
    }

    public function store(Request $request, Family $family, MercadoPagoService $mp): RedirectResponse
    {
        $user = $request->user();

        if ($redirect = $this->blocker($user, $family)) {
            return $redirect;
        }

        // Validate digits only; the mask characters are added back on save.
        $request->merge([
            'cpf'   => preg_replace('/\D/', '', (string) $request->input('cpf')),
            'phone' => preg_replace('/\D/', '', (string) $request->input('phone')),
        ]);

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

        // One row per user and family (unique index): a new attempt reuses the
        // row of an abandoned, failed or refunded one. A paid row is blocked above.
        $purchase = FamilyPurchase::firstOrNew([
            'user_id'   => $user->id,
            'family_id' => $family->id,
        ]);

        $purchase->fill([
            'amount'         => $family->price,
            'status'         => 'pending',
            'payment_id'     => null,
            'payment_method' => null,
            'paid_at'        => null,
        ])->save();

        try {
            $preference = $mp->createPreference($purchase, $user, $family);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'payment' => 'Não foi possível iniciar o pagamento agora. Tente novamente em instantes.',
            ]);
        }

        return redirect()->away($preference['init_point']);
    }

    /**
     * Where Mercado Pago sends the customer back after the payment screen.
     * The payment is read from Mercado Pago right now because the webhook may
     * arrive later (or not at all in a local environment).
     */
    public function finish(
        Request $request,
        FamilyPurchase $purchase,
        MercadoPagoService $mp,
        PurchaseSyncService $sync,
    ): View {
        abort_unless($purchase->user_id === $request->user()->id, 404);

        // payment_id comes in the query string, so it is only a hint: the payment
        // is fetched from Mercado Pago and its external_reference must match
        // this purchase (checked inside syncPayment through the reference).
        $paymentId = (string) $request->query('payment_id');

        if (ctype_digit($paymentId)) {
            try {
                $payment = $mp->getPayment($paymentId);

                if (($payment['external_reference'] ?? null) === 'purchase-' . $purchase->id) {
                    $sync->syncPayment($payment);
                    $purchase->refresh();
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        $purchase->load('family');

        return view('site.revit.families.purchase-finish', ['purchase' => $purchase]);
    }

    /**
     * Where this user must go instead of the purchase page, or null when they can proceed.
     */
    private function blocker(User $user, Family $family): ?RedirectResponse
    {
        abort_unless($family->isPublished() && ! $family->is_free && ($family->price ?? 0) > 0, 404);

        if ($user->delete_requested_at) {
            return redirect()->route('account.danger')
                ->with('status', 'Você pediu o encerramento da conta. Cancele o pedido para poder comprar.');
        }

        if ($user->hasPurchasedFamily($family->id)) {
            return redirect()->route('revit.families.show', ['family' => $family->slug])
                ->with('status', 'Você já comprou esta família.');
        }

        return null;
    }
}