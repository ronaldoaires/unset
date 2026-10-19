<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legal documents
    |--------------------------------------------------------------------------
    |
    | "version" identifies the current Terms of Use and Privacy Policy. When it
    | changes, users are asked to accept again on their next checkout.
    |
    */

    'version'    => '2026-10-18',
    'updated_at' => '18/10/2026',

    // Who operates the platform (shown in both documents).
    'owner'    => env('LEGAL_OWNER_NAME', config('app.name')),
    'document' => env('LEGAL_OWNER_DOCUMENT'), // CNPJ or CPF; hidden when empty
    'city'     => env('LEGAL_CITY', 'Nova Xavantina/MT'),
    'email'    => env('LEGAL_CONTACT_EMAIL', 'ceo@unset.com.br'),

];