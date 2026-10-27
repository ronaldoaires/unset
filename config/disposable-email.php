<?php

/*
 * propaganistas/laravel-disposable-email, adjusted for this project.
 * Used through App\Rules\NotDisposableEmail (not the "indisposable" string rule).
 */
return [

    // Only used by "php artisan disposable:update", which is optional: the
    // package ships the list and refreshes it on every composer update.
    'sources' => [
        'https://cdn.jsdelivr.net/gh/disposable/disposable-email-domains@master/domains.json',
    ],

    'fetcher' => \Propaganistas\LaravelDisposableEmail\Fetcher\DefaultFetcher::class,

    // Missing file = the list bundled with the package. Tests point this to a
    // tiny fixture (see phpunit.xml) instead of the 1.8 MB list.
    'storage' => env('DISPOSABLE_DOMAINS_PATH')
        ? base_path(env('DISPOSABLE_DOMAINS_PATH'))
        : storage_path('framework/disposable_domains.json'),

    'whitelist' => [],

    // "m.spheremail.net" is caught when "spheremail.net" is listed.
    'include_subdomains' => true,

    // Off: the list would be stored as one 1.8 MB row in the database cache.
    // Reading the file only happens on sign-up, which is rare and fast enough.
    'cache' => [
        'enabled' => false,
        'store'   => 'default',
        'key'     => 'disposable_email:domains',
    ],

    /*
     * Project addition: domains seen in spam sign-ups that the public list
     * does not have yet. Subdomains are blocked too.
     */
    'blocked' => [
        'spheremail.net',
    ],

];
