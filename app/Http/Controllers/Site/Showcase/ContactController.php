<?php

namespace App\Http\Controllers\Site\Showcase;

use App\Enums\LeadType;
use App\Http\Controllers\Controller;
use App\Services\LeadService;
use App\Support\BotTrap;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * "Fale conosco": the short form in the footer of every page (and on
 * /contato). Its fields are prefixed with "contact_" and its errors use
 * the "contact" bag, so they never mix with another form on the same page.
 */
class ContactController extends Controller
{
    public const BAG = 'contact';

    private const FIELDS = [
        'name'    => 'contact_name',
        'email'   => 'contact_email',
        'message' => 'contact_message',
    ];

    public function show(): View
    {
        return view('site.showcase.contact');
    }

    public function store(Request $request, LeadService $leads): RedirectResponse
    {
        try {
            if (! BotTrap::verify($request, self::FIELDS['message'])) {
                return redirect()->route('leads.thanks')->with('lead_type', LeadType::Contact->value);
            }

            LeadService::normalize($request, self::FIELDS);

            $rules = [];

            foreach (LeadService::contactRules() as $field => $fieldRules) {
                $rules[self::FIELDS[$field]] = $fieldRules;
            }

            $rules['contact_privacy'] = ['accepted'];

            $data = Validator::make($request->all(), $rules, [
                'contact_privacy.accepted' => 'Para enviar, aceite a Política de Privacidade.',
            ], [
                'contact_name'    => 'nome',
                'contact_email'   => 'e-mail',
                'contact_message' => 'mensagem',
            ])->validate();
        } catch (ValidationException $exception) {
            // Back to the form itself (the footer is at the bottom of a long page).
            return redirect()->to(url()->previous() . '#fale-conosco')
                ->withErrors($exception->errors(), self::BAG)
                ->withInput($request->except(['_token']));
        }

        $leads->capture(LeadType::Contact, [
            'name'    => $data['contact_name'],
            'email'   => $data['contact_email'],
            'message' => $data['contact_message'],
        ], $request);

        return redirect()->route('leads.thanks')->with('lead_type', LeadType::Contact->value);
    }
}
