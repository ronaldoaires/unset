<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                // email_verified_at is not mass assignable, so it is set after
                // creation. Accounts created by the superadmin start verified.
                ->after(fn (User $record) => $record->forceFill(['email_verified_at' => now()])->save())
                ->successNotificationTitle(fn (User $record) => 'Usuário ' . $record->name . ' criado'),
        ];
    }
}