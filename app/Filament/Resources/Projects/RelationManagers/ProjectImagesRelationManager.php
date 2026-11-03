<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ProjectImage;
use App\Services\ImageService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;

/**
 * Project gallery: several photos are uploaded at once (each one gets a
 * default alt text from the project title) and can then be captioned,
 * have the alt text refined and be reordered by dragging.
 */
class ProjectImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Galeria';

    protected static ?string $modelLabel = 'Foto';

    protected static ?string $pluralModelLabel = 'Fotos';

    // Used by the edit modal only; uploads go through the "Enviar fotos" action.
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('alt')
                ->label('Texto alternativo')
                ->helperText('Descreva o que aparece na foto. Usado por leitores de tela e pelo Google Imagens.')
                ->required()
                ->maxLength(255),

            TextInput::make('caption')
                ->label('Legenda')
                ->helperText('Opcional, aparece abaixo da foto.')
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alt')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('thumb_url')
                    ->label('')
                    ->imageHeight(64),

                TextColumn::make('alt')
                    ->label('Texto alternativo')
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('caption')
                    ->label('Legenda')
                    ->limit(40)
                    ->placeholder('—')
                    ->visibleFrom('md'),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Enviar fotos')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        FileUpload::make('photos')
                            ->label('Fotos')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(30)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(5120)
                            ->storeFiles(false)
                            ->required()
                            ->helperText('Até 30 fotos por vez, 5 MB cada. Você pode ajustar o texto de cada uma depois.'),
                    ])
                    ->action(fn (array $data) => $this->storePhotos($data['photos'] ?? [])),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip('Editar textos da foto')
                    ->successNotificationTitle('Foto atualizada'),

                DeleteAction::make()
                    ->iconButton()
                    ->size(Size::Large)
                    ->tooltip('Excluir foto')
                    ->successNotificationTitle('Foto excluída'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Each photo goes through ImageService (webp + thumbnail), named after
     * the project slug so the files are self-describing for image search.
     *
     * @param  array<int, UploadedFile>  $photos
     */
    protected function storePhotos(array $photos): void
    {
        $project  = $this->getOwnerRecord();
        $images   = app(ImageService::class);
        $position = (int) $project->images()->max('sort_order');
        $number   = $project->images()->count();

        foreach ($photos as $photo) {
            if (! $photo instanceof UploadedFile) {
                continue;
            }

            $number++;
            $position++;

            ProjectImage::create([
                'project_id' => $project->id,
                'path'       => $images->storeWithSlug(
                    slug: $project->slug . '-' . $number,
                    file: $photo,
                    directory: 'projects/gallery',
                    disk: 'public',
                ),
                'alt'        => $project->title . ' - foto ' . $number,
                'sort_order' => $position,
            ]);
        }

        Notification::make()
            ->title(count($photos) . ' foto(s) adicionada(s) à galeria')
            ->success()
            ->send();
    }
}
