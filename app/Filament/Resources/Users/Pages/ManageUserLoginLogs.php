<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ManageUserLoginLogs extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'loginLogs';

    public function getTitle(): string|Htmlable
    {
        return 'Acessos de ' . $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Voltar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(UserResource::getUrl('index')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('acesso')
            ->pluralModelLabel('acessos')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('success')
                    ->label('Resultado')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Sucesso' : 'Falha')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('user_agent')
                    ->label('Navegador')
                    ->limit(50)
                    ->tooltip(fn (?string $state) => $state)
                    ->visibleFrom('md'),
            ])
            ->filters([
                TernaryFilter::make('success')
                    ->label('Resultado')
                    ->trueLabel('Somente sucessos')
                    ->falseLabel('Somente falhas'),
            ])
            // Read-only audit trail: no header, record or bulk actions.
            ->defaultSort('created_at', 'desc');
    }
}