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
                ['label' => 'Avatar',  'route' => 'account.avatar',   'icon' => 'photo',       'enabled' => true],
            ],
        ],
        [
            'label' => 'Conteúdo',
            'items' => [
                ['label' => 'Downloads',  'route' => 'account.downloads',    'icon' => 'arrow-down-tray', 'enabled' => true],
                ['label' => 'Favoritos',  'route' => 'account.favorites',    'icon' => 'heart',           'enabled' => true],
                ['label' => 'Compras',    'route' => 'account.purchases',    'icon' => 'shopping-bag',    'enabled' => true],
                ['label' => 'Assinatura', 'route' => 'account.subscription', 'icon' => 'credit-card',     'enabled' => true],
            ],
        ],
        [
            'label' => 'Conta',
            'items' => [
                ['label' => 'Encerrar conta', 'route' => 'account.danger', 'icon' => 'shield-exclamation', 'enabled' => true, 'danger' => true],
            ],
        ],
    ],

];