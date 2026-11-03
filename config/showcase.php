<?php

/*
|--------------------------------------------------------------------------
| Services showcase
|--------------------------------------------------------------------------
|
| Fixed texts of the engineering/technology services pages and the
| identity of the company behind them. Everything the visitor reads is
| in Portuguese; change it here without touching the views.
|
*/

return [

    // Private contact data: read only on the server, never printed in a page.
    'contact' => [
        // Digits with country code, e.g. 5566981068854. Used by the /whatsapp redirect.
        'whatsapp'    => env('SHOWCASE_WHATSAPP'),
        // Inbox that receives quote requests and messages (defaults to the mail sender).
        'leads_email' => env('SHOWCASE_LEADS_EMAIL', env('MAIL_FROM_ADDRESS')),
        // Promise shown after sending and in the auto-reply.
        'reply_time'  => 'em até 1 dia útil',
    ],

    'company' => [
        'name'         => 'UnSet',
        'area_served'  => 'Brasil',
        'service_mode' => 'Atendimento online para todo o Brasil',
    ],

    // Shown on every service page: in engineering the person signing the
    // project matters as much as the project itself.
    'responsible' => [
        'name'        => 'Ronaldo Aires',
        'role'        => 'Responsável técnico',
        'title'       => 'Engenheiro Civil',
        'registry'    => 'CREA-MT 62629',
        'credentials' => [
            'Engenheiro Civil',
            'MBA em Gestão de Obras',
            'Pós-graduação em BIM em Gerenciamento de Projetos',
            'Desenvolvedor de sistemas web e aplicativos',
        ],
        'links' => [
            'LinkedIn' => 'https://www.linkedin.com/in/ronaldoaires/',
            'GitHub'   => 'https://github.com/ronaldoaires',
        ],
    ],

    // Steps of the "Como funciona" section, shared by every service.
    'steps' => [
        ['title' => 'Contato',     'text' => 'Você conta o que precisa pelo formulário ou WhatsApp.'],
        ['title' => 'Orçamento',   'text' => 'Analisamos o pedido e enviamos uma proposta clara, com prazo e valor.'],
        ['title' => 'Execução',    'text' => 'O projeto é desenvolvido com acompanhamento e revisões combinadas.'],
        ['title' => 'Entrega',     'text' => 'Você recebe tudo pronto e conta com suporte após a entrega.'],
    ],

    // Intro of each area page, keyed by App\Enums\ServiceArea values.
    'areas' => [
        'engineering' => [
            'heading'     => 'Serviços de engenharia',
            'headline'    => 'Projetos de engenharia completos, com responsável técnico registrado no CREA.',
            'description' => 'Projetos arquitetônico, estrutural, elétrico e hidrossanitário feitos em BIM, compatibilizados entre si e prontos para aprovação e execução.',
            'icon'        => 'building-office-2',
        ],
        'technology' => [
            'heading'     => 'Serviços de tecnologia',
            'headline'    => 'Sites, sistemas e aplicativos modernos, feitos para o seu negócio crescer.',
            'description' => 'Desenvolvimento de sites, sistemas web, softwares sob medida e aplicativos, com foco em desempenho, segurança e resultado.',
            'icon'        => 'code-bracket',
        ],
    ],

];
