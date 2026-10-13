<?php

namespace App\Filament\Resources\FamilyPurchases\Pages;

use App\Filament\Resources\FamilyPurchases\FamilyPurchaseResource;
use Filament\Resources\Pages\ManageRecords;

class ManageFamilyPurchases extends ManageRecords
{
    protected static string $resource = FamilyPurchaseResource::class;

    // No header actions: purchases are never created from the panel.
    protected function getHeaderActions(): array
    {
        return [];
    }
}