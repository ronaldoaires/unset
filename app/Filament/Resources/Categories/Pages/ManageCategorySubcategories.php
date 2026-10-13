<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Subcategory;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ManageCategorySubcategories extends ManageRelatedRecords
{
    protected static string $resource = CategoryResource::class;

    protected static string $relationship = 'subcategories';

    public function getTitle(): string|Htmlable
    {
        return 'Subcategorias de ' . $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Voltar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(CategoryResource::getUrl('index')),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                TextInput::make('sort_order')
                    ->label('Ordem de exibição')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Toggle::make('is_active')
                    ->label('Ativa')
                    ->default(true)
                    ->inline(false),

                Textarea::make('description')
                    ->label('Descrição')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('subcategoria')
            ->pluralModelLabel('subcategorias')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Ordem')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),

                TextColumn::make('families_count')
                    ->label('Famílias')
                    ->counts('families')
                    ->visibleFrom('md'),

                TextColumn::make('view_count')
                    ->label('Views')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_viewed_at')
                    ->label('Última visita')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->sortable()
                    ->visibleFrom('md'),

                ToggleColumn::make('is_active')
                    ->label('Ativa')
                    ->disabled(fn(Subcategory $record) => ! auth()->user()->can('update', $record))
                    ->afterStateUpdated(function (Subcategory $record, bool $state) {
                        Notification::make()
                            ->title('Subcategoria ' . $record->name . ($state ? ' ativada' : ' desativada'))
                            ->success()
                            ->send();
                    })
                    ->tooltip(fn(Subcategory $record) => 'Clique para ' . ($record->is_active ? 'desativar' : 'ativar') . ' a subcategoria'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        // Slug uniqueness is scoped per category, not global.
                        $data['slug'] = SlugGenerator::unique(
                            modelClass: Subcategory::class,
                            title: $data['name'],
                            queryModifier: fn($query) => $query->where('category_id', $this->getRecord()->getKey()),
                        );

                        return $data;
                    })
                    ->successNotificationTitle(fn(Subcategory $record) => 'Subcategoria ' . $record->name . ' criada'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(Subcategory $record) => 'Editar ' . $record->name)
                    ->successNotificationTitle(fn(Subcategory $record) => 'Subcategoria ' . $record->name . ' atualizada'),

                DeleteAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip(fn(Subcategory $record) => 'Excluir ' . $record->name)
                    ->before(function (DeleteAction $action, Subcategory $record) {
                        if ($record->families()->exists()) {
                            Notification::make()
                                ->title('Não é possível excluir')
                                ->body('A subcategoria ' . $record->name . ' possui famílias vinculadas.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->successNotificationTitle(fn(Subcategory $record) => 'Subcategoria ' . $record->name . ' excluída'),
            ])
            ->defaultSort('sort_order');
    }
}
