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
    |
    */

    'nav' => [
        ['label' => 'Início',    'route' => 'home',            'icon' => 'home',               'enabled' => true,  'bottom' => true],
        // "active" widens the highlight to every page of the section.
        ['label' => 'Famílias',  'route' => 'revit.families.index', 'active' => 'revit.families.*', 'icon' => 'rectangle-stack', 'enabled' => true, 'bottom' => true],
        
        ['label' => 'Templates', 'route' => 'templates.index', 'icon' => 'document-duplicate', 'enabled' => false, 'bottom' => false],
        ['label' => 'Planilhas', 'route' => 'sheets.index',    'icon' => 'table-cells',        'enabled' => false, 'bottom' => false],
        ['label' => 'Cursos',    'route' => 'courses.index',   'icon' => 'academic-cap',       'enabled' => false, 'bottom' => false],
    ],

];
