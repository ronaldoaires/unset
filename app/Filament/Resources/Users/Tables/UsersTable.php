<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\IconColumn;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular()
                    // Same look as the site's initials circle, drawn as an inline SVG.
                    ->defaultImageUrl(function (User $record): string {
                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">'
                            . '<rect width="40" height="40" fill="#dbe8ff"/>'
                            . '<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="sans-serif" font-size="16" font-weight="600" fill="#093e92">'
                            . e($record->initials)
                            . '</text></svg>';

                        return 'data:image/svg+xml;base64,' . base64_encode($svg);
                    }),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn(User $record) => $record->delete_requested_at
                        ? 'Exclusão solicitada em ' . $record->delete_requested_at->format('d/m/Y')
                        : null),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('role')
                    ->label('Perfil')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        User::ROLE_SUPERADMIN => 'Superadmin',
                        User::ROLE_ADMIN      => 'Moderador',
                        default               => 'Usuário',
                    })
                    ->color(fn(string $state) => match ($state) {
                        User::ROLE_SUPERADMIN => 'danger',
                        User::ROLE_ADMIN      => 'warning',
                        default               => 'gray',
                    })
                    ->sortable(),

                IconColumn::make('email_verified')
                    ->label('E-mail verificado')
                    ->boolean()
                    ->state(fn(User $record) => $record->email_verified_at !== null)
                    ->visibleFrom('md'),

                TextColumn::make('activeSubscription.plan.name')
                    ->label('Plano')
                    ->placeholder('Sem plano')
                    ->visibleFrom('md'),

                TextColumn::make('families_count')
                    ->label('Famílias')
                    ->counts('families')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('last_login_at')
                    ->label('Último acesso')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('created_at')
                    ->label('Cadastro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('Ativa')
                    ->sortable()
                    // The toggle writes straight to the record, so the policy must be checked by hand.
                    // Nobody can deactivate their own account from here.
                    ->disabled(fn(User $record) => ! auth()->user()->can('update', $record) || $record->is(auth()->user()))
                    ->afterStateUpdated(function (User $record, bool $state) {
                        Notification::make()
                            ->title('Conta de ' . $record->name . ($state ? ' ativada' : ' desativada'))
                            ->success()
                            ->send();
                    })
                    ->tooltip(fn(User $record) => 'Clique para ' . ($record->is_active ? 'desativar' : 'ativar') . ' a conta'),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Perfil')
                    ->options([
                        User::ROLE_USER       => 'Usuário',
                        User::ROLE_ADMIN      => 'Moderador',
                        User::ROLE_SUPERADMIN => 'Superadmin',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Conta ativa'),

                TernaryFilter::make('email_verified_at')
                    ->label('E-mail verificado')
                    ->nullable(),

                TernaryFilter::make('delete_requested_at')
                    ->label('Exclusão solicitada')
                    ->nullable(),
            ])
            ->recordActions([
                Action::make('loginLogs')
                    ->icon('heroicon-o-clock')
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(User $record) => 'Acessos de ' . $record->name)
                    ->url(fn(User $record) => UserResource::getUrl('login-logs', ['record' => $record])),

                // Read-only modal: shows 2FA status and the last password change.
                // The secret and recovery codes are never displayed.
                Action::make('security')
                    ->icon('heroicon-o-shield-check')
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(User $record) => 'Segurança de ' . $record->name)
                    ->modalHeading(fn(User $record) => 'Segurança de ' . $record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->schema(fn(User $record) => [
                        TextEntry::make('two_factor')
                            ->label('Autenticação em dois fatores')
                            ->state($record->security?->two_factor_enabled ? 'Ativada' : 'Desativada'),

                        TextEntry::make('password_changed_at')
                            ->label('Última troca de senha')
                            ->state($record->security?->password_changed_at?->format('d/m/Y H:i') ?? 'Sem registro'),
                    ]),
                EditAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(User $record) => 'Editar ' . $record->name)
                    ->successNotificationTitle(fn(User $record) => 'Usuário ' . $record->name . ' atualizado'),

                // The policy already hides this action on the user's own row.
                DeleteAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(User $record) => 'Excluir ' . $record->name)
                    // Block deletion while the account has history in the marketplace.
                    ->before(function (DeleteAction $action, User $record) {
                        if (
                            $record->families()->exists()
                            || $record->subscriptions()->exists()
                            || $record->familyPurchases()->exists()
                        ) {
                            Notification::make()
                                ->title('Não é possível excluir')
                                ->body('A conta de ' . $record->name . ' possui famílias, assinaturas ou compras. Desative a conta em vez de excluir.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->successNotificationTitle(fn(User $record) => 'Usuário ' . $record->name . ' excluído'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
