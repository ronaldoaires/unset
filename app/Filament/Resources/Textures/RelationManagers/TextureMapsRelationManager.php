<?php

namespace App\Filament\Resources\Textures\RelationManagers;

use App\Filament\Resources\Textures\TextureResource;
use App\Models\TextureMap;
use App\Services\TextureMapService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;

/**
 * Maps of one texture, grouped by resolution. Files are never edited in
 * place: a new upload of the same type and resolution replaces the old one,
 * and every change goes through TextureMapService (version + preview).
 */
class TextureMapsRelationManager extends RelationManager
{
    protected static string $relationship = 'maps';

    protected static ?string $title = 'Mapas';

    public function table(Table $table): Table
    {
        $canEdit = fn() => ! TextureResource::isLocked($this->getOwnerRecord())
            && auth()->user()->can('update', $this->getOwnerRecord());

        return $table
            ->recordTitleAttribute('original_name')
            ->defaultGroup('resolution')
            ->columns([
                TextColumn::make('resolution')
                    ->label('Resolução')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => strtoupper($state)),

                TextColumn::make('type')
                    ->label('Mapa')
                    ->formatStateUsing(fn(string $state) => TextureMap::TYPES[$state] ?? $state),

                TextColumn::make('dimensions')
                    ->label('Tamanho'),

                TextColumn::make('formatted_size')
                    ->label('Arquivo'),

                TextColumn::make('original_name')
                    ->label('Nome original')
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                Action::make('uploadMany')
                    ->label('Enviar mapas')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible($canEdit)
                    ->schema([
                        FileUpload::make('files')
                            ->label('Arquivos')
                            ->multiple()
                            ->required()
                            ->acceptedFileTypes(TextureMapService::MIME_TYPES)
                            ->maxSize(51200)
                            ->maxFiles(10)
                            ->storeFiles(false)
                            ->helperText('O tipo vem do nome do arquivo e a resolução vem do tamanho em pixels. Substitui o mapa igual que já existir.'),
                    ])
                    ->action(function (array $data) {
                        $files  = array_filter((array) $data['files'], fn($f) => $f instanceof UploadedFile);
                        $result = app(TextureMapService::class)->storeMany($this->getOwnerRecord(), $files);

                        if ($result['stored']) {
                            Notification::make()
                                ->title('Mapas enviados')
                                ->body(implode(', ', $result['stored']))
                                ->success()
                                ->send();
                        }

                        if ($result['skipped']) {
                            Notification::make()
                                ->title('Arquivos não reconhecidos')
                                ->body(implode(', ', $result['skipped']) . '. Use "Enviar um mapa" para escolher o tipo.')
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('uploadOne')
                    ->label('Enviar um mapa')
                    ->icon('heroicon-o-plus')
                    ->color('gray')
                    ->visible($canEdit)
                    ->schema([
                        Select::make('type')
                            ->label('Tipo de mapa')
                            ->options(TextureMap::TYPES)
                            ->required()
                            ->native(false),

                        FileUpload::make('file')
                            ->label('Arquivo')
                            ->required()
                            ->acceptedFileTypes(TextureMapService::MIME_TYPES)
                            ->maxSize(51200)
                            ->storeFiles(false),
                    ])
                    ->action(function (array $data) {
                        if (! $data['file'] instanceof UploadedFile) {
                            return;
                        }

                        $map = app(TextureMapService::class)->store($this->getOwnerRecord(), $data['file'], $data['type']);

                        Notification::make()
                            ->title($map->type_label . ' ' . strtoupper($map->resolution) . ' enviado')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('delete')
                    ->label('Excluir')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->iconButton()
                    ->visible($canEdit)
                    ->requiresConfirmation()
                    ->action(fn(TextureMap $record) => app(TextureMapService::class)->delete($record)),
            ]);
    }
}
