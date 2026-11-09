<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Main navigation
    |--------------------------------------------------------------------------
    |
    | Shared by the desktop header and the mobile bottom bar. Items with
    | "enabled" => false are not rendered at all: switch them on as each
    | page ships. "bottom" => true also shows the item in the mobile bar
    | (keep it to 3 or 4 items). "icon" is a Heroicons outline name.
    | "submenu" => "services" opens the engineering/technology panel on
    | desktop instead of navigating (the mobile bar still links to the hub).
    |
    */

    'nav' => [
        ['label' => 'Início',    'route' => 'home',            'icon' => 'home',               'enabled' => true,  'bottom' => true],
        // "active" widens the highlight to every page of the section.
        ['label' => 'Famílias',  'route' => 'revit.families.index', 'active' => 'revit.families.*', 'icon' => 'rectangle-stack', 'enabled' => true, 'bottom' => true],
        ['label' => 'Texturas PBR', 'route' => 'revit.textures.index', 'active' => 'revit.textures.*', 'icon' => 'swatch', 'enabled' => true, 'bottom' => true],
        ['label' => 'Serviços',  'route' => 'services.index',  'active' => 'services.*',  'icon' => 'briefcase', 'enabled' => true, 'bottom' => true, 'submenu' => 'services'],
        ['label' => 'Portfólio', 'route' => 'portfolio.index', 'active' => 'portfolio.*', 'icon' => 'photo',     'enabled' => false, 'bottom' => false], // hidden until there are projects
        
        ['label' => 'Templates', 'route' => 'templates.index', 'icon' => 'document-duplicate', 'enabled' => false, 'bottom' => false],
        ['label' => 'Planilhas', 'route' => 'sheets.index',    'icon' => 'table-cells',        'enabled' => false, 'bottom' => false],
        ['label' => 'Cursos',    'route' => 'courses.index',   'icon' => 'academic-cap',       'enabled' => false, 'bottom' => false],
    ],

    /*
    |--------------------------------------------------------------------------
    | Announcement bar
    |--------------------------------------------------------------------------
    |
    | Thin bar above the header on every public page. Switch it off (or edit
    | the text in components/site/announcement.blade.php) when the launch
    | phase is over.
    |
    */

    'announcement' => [
        'enabled' => env('SITE_ANNOUNCEMENT', true),
    ],

];
