<?php

namespace App\Filament\Resources\PendingDeletions;

use App\Filament\Resources\PendingDeletions\Pages\ManagePendingDeletions;
use App\Filament\Resources\PendingDeletions\Tables\PendingDeletionsTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Queue of accounts waiting to be closed. It is the manual fallback for the
 * scheduled "accounts:purge" command, in case the cron job is not running.
 * Same model as UserResource, so it needs its own slug.
 */
class PendingDeletionResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'exclusoes-solicitadas';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-minus';

    protected static ?string $navigationLabel = 'Exclusões solicitadas';

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?string $modelLabel = 'Exclusão solicitada';

    protected static ?string $pluralModelLabel = 'Exclusões solicitadas';

    protected static ?int $navigationSort = 11;

    public static function getEloquentQuery(): Builder
    {
        // Anonymized accounts have no e-mail anymore, so they drop out of the queue.
        return parent::getEloquentQuery()
            ->whereNotNull('delete_requested_at')
            ->whereNotNull('email')
            ->where('role', User::ROLE_USER)
            ->withExists(['subscriptions', 'familyPurchases']);
    }

    /**
     * Counts the accounts whose grace period is already over: those should
     * have been closed by the cron job, so any number here is a warning.
     */
    public static function getNavigationBadge(): ?string
    {
        $overdue = User::dueForDeletion()->where('role', User::ROLE_USER)->count();

        return $overdue > 0 ? (string) $overdue : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Contas com prazo vencido';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return PendingDeletionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePendingDeletions::route('/'),
        ];
    }
}