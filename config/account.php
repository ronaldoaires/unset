<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Account area navigation
    |--------------------------------------------------------------------------
    |
    | Shared by the desktop sidebar and the mobile tabs. Items with
    | "enabled" => false are not rendered: switch them on as each page ships.
    | "icon" is a Heroicons outline name. "danger" paints the item red.
    |
    */

    'nav' => [
        [
            'label' => 'Principal',
            'items' => [
                ['label' => 'Início',  'route' => 'account.index',    'icon' => 'home',        'enabled' => true],
                ['label' => 'Perfil',  'route' => 'account.profile',  'icon' => 'user',        'enabled' => true],
                ['label' => 'Senha',   'route' => 'account.password', 'icon' => 'lock-closed', 'enabled' => true],
                ['label' => 'E-mail',  'route' => 'account.email',    'icon' => 'at-symbol',   'enabled' => true],
                ['label' => 'Avatar',  'route' => 'account.avatar',   'icon' => 'photo',       'enabled' => false],
            ],
        ],
        [
            'label' => 'Conteúdo',
            'items' => [
                ['label' => 'Downloads',  'route' => 'account.downloads',    'icon' => 'arrow-down-tray', 'enabled' => false],
                ['label' => 'Favoritos',  'route' => 'account.favorites',    'icon' => 'heart',           'enabled' => false],
                ['label' => 'Assinatura', 'route' => 'account.subscription', 'icon' => 'credit-card',     'enabled' => false],
            ],
        ],
        [
            'label' => 'Conta',
            'items' => [
                ['label' => 'Encerrar conta', 'route' => 'account.danger', 'icon' => 'shield-exclamation', 'enabled' => false, 'danger' => true],
            ],
        ],
    ],

];