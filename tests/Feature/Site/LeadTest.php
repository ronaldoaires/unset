<?php

use App\Enums\LeadType;
use App\Enums\ServiceArea;
use App\Models\Lead;
use App\Models\Service;
use App\Notifications\LeadReceivedNotification;
use App\Notifications\NewLeadNotification;
use App\Support\BotTrap;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Quote requests, "Fale conosco" and the WhatsApp redirect
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Notification::fake();

    config([
        'showcase.contact.leads_email' => 'ceo@unset.com.br',
        'showcase.contact.whatsapp'    => '5566981068854',
    ]);

    $this->service = Service::create([
        'area'      => ServiceArea::Engineering,
        'name'      => 'Projeto Estrutural',
        'slug'      => 'projeto-estrutural',
        'summary'   => 'Cálculo e detalhamento de estruturas.',
        'is_active' => true,
    ]);
});

function quoteData(array $overrides = []): array
{
    return array_merge([
        'name'              => '  Maria   Souza ',
        'email'             => 'Maria.Souza@gmail.com',
        'phone'             => '(66) 99999-1234',
        'city'              => 'Nova Xavantina',
        'state'             => 'MT',
        'service_id'        => test()->service->id,
        'details'           => ['area' => '160', 'floors' => '2', 'kind' => 'Aplicativo', 'budget' => 'Valor inventado'],
        'message'           => 'Preciso do projeto estrutural de um sobrado.',
        'privacy'           => '1',
        BotTrap::TIME_FIELD => formToken(),
    ], $overrides);
}

function contactData(array $overrides = []): array
{
    return array_merge([
        'contact_name'      => 'João Lima',
        'contact_email'     => 'joao.lima@gmail.com',
        'contact_message'   => 'Vocês fazem projeto elétrico para galpão?',
        'contact_privacy'   => '1',
        BotTrap::TIME_FIELD => formToken(),
    ], $overrides);
}

it('shows the quote form with the service pre-selected', function () {
    $this->get('/orcamento?servico=projeto-estrutural')
        ->assertOk()
        ->assertSee('Orçamento de projeto estrutural')
        ->assertSee('Área aproximada (m²)');
});

it('saves a quote request and e-mails the owner and the client', function () {
    $this->withSession(['utm' => ['utm_source' => 'instagram']])
        ->post('/orcamento', quoteData())
        ->assertRedirect(route('leads.thanks'));

    $lead = Lead::sole();

    expect($lead->type)->toBe(LeadType::Quote)
        ->and($lead->name)->toBe('Maria Souza')
        ->and($lead->email)->toBe('maria.souza@gmail.com')
        ->and($lead->phone)->toBe('66999991234')
        ->and($lead->service_id)->toBe($this->service->id)
        ->and($lead->utm_source)->toBe('instagram')
        ->and($lead->privacy_accepted_at)->not->toBeNull()
        // Only the engineering answers are kept, stored under their labels.
        ->and($lead->details)->toBe(['Área aproximada (m²)' => '160', 'Pavimentos' => '2']);

    Notification::assertSentTo(
        new AnonymousNotifiable,
        NewLeadNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'ceo@unset.com.br',
    );

    Notification::assertSentTo(
        new AnonymousNotifiable,
        LeadReceivedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'maria.souza@gmail.com',
    );

    $this->withSession(['lead_type' => 'quote'])->get(route('leads.thanks'))->assertOk()->assertSee('Pedido de orçamento enviado!');
});

it('requires WhatsApp and the privacy acceptance on the quote form', function () {
    $this->post('/orcamento', quoteData(['phone' => '123', 'privacy' => null]))
        ->assertSessionHasErrors(['phone', 'privacy']);

    expect(Lead::count())->toBe(0);
});

it('rejects spam-looking messages', function () {
    $this->post('/orcamento', quoteData(['message' => 'Compre já <a href="http://spam.test">aqui</a>']))
        ->assertSessionHasErrors('message');

    $this->post('/orcamento', quoteData(['message' => 'http://a.test http://b.test http://c.test promo']))
        ->assertSessionHasErrors('message');

    expect(Lead::count())->toBe(0);
});

it('silently drops bots that fill the honeypot', function () {
    $this->post('/orcamento', quoteData([BotTrap::HONEYPOT_FIELD => 'http://spam.test']))
        ->assertRedirect(route('leads.thanks'));

    expect(Lead::count())->toBe(0);
    Notification::assertNothingSent();
});

it('refuses forms sent faster than a person can type', function () {
    $this->post('/orcamento', quoteData([BotTrap::TIME_FIELD => BotTrap::token()]))
        ->assertSessionHasErrors('message');

    expect(Lead::count())->toBe(0);
});

it('ignores a double submission of the same message', function () {
    $this->post('/orcamento', quoteData());
    $this->post('/orcamento', quoteData());

    expect(Lead::count())->toBe(1);
});

it('saves a "Fale conosco" message', function () {
    $this->post('/contato', contactData())->assertRedirect(route('leads.thanks'));

    $lead = Lead::sole();

    expect($lead->type)->toBe(LeadType::Contact)
        ->and($lead->name)->toBe('João Lima')
        ->and($lead->phone)->toBeNull();
});

it('keeps "Fale conosco" errors in their own bag and returns to the form', function () {
    $this->from('/contato')
        ->post('/contato', contactData(['contact_email' => 'nao-e-email']))
        ->assertRedirect(url('/contato') . '#fale-conosco')
        ->assertSessionHasErrors(['contact_email'], null, 'contact');

    expect(Lead::count())->toBe(0);
});

it('keeps the "Fale conosco" form on its own page, with only a link in the footer', function () {
    $this->get('/contato')->assertOk()->assertSee('id="fale-conosco"', false);

    $this->get('/servicos/engenharia')
        ->assertOk()
        ->assertDontSee('id="fale-conosco"', false)
        ->assertSee(route('contact.show'), false);
});

it('never prints the WhatsApp number in the pages', function () {
    $this->get('/servicos/engenharia/projeto-estrutural')
        ->assertOk()
        ->assertSee(route('whatsapp'), false)
        ->assertDontSee('981068854')
        ->assertDontSee('wa.me');
});

it('redirects to WhatsApp with a message about the service', function () {
    $response = $this->post('/whatsapp', ['servico' => 'projeto-estrutural']);

    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toStartWith('https://wa.me/5566981068854?text=')
        ->toContain(rawurlencode('projeto estrutural'));
});

it('does not answer the WhatsApp redirect to plain links', function () {
    $this->get('/whatsapp')->assertMethodNotAllowed();
});
