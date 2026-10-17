<?php

namespace App\Filament\Resources\PendingDeletions\Pages;

use App\Filament\Resources\PendingDeletions\PendingDeletionResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePendingDeletions extends ManageRecords
{
    protected static string $resource = PendingDeletionResource::class;

    // No header actions: requests only come from the user's own account page.
    protected function getHeaderActions(): array
    {
        return [];
    }
}