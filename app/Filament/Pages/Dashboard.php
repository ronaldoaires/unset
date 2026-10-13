<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // Label shown in the sidebar menu.
    protected static ?string $navigationLabel = 'Dashboard';

    // Heading shown at the top of the page and in the browser tab.
    protected static ?string $title = 'Dashboard';
}