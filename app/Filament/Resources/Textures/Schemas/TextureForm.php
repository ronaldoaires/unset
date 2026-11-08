<?php

namespace App\Filament\Resources\Textures\Schemas;

use App\Filament\Resources\Textures\TextureResource;
use App\Models\Texture;
use App\Models\TextureCategory;
use App\Services\TextureMapService;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TextureForm
{
    public static function configure(Schema $schema): Schema
    {
        $locked = fn(?Texture $record) => TextureResource::isLocked($record);

        return $schema->components([

            Section::make('Informações básicas')
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),

                    Select::make('texture_category_id')
                        ->label('Categoria')
                        ->options(fn() => TextureCategory::query()->active()->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        // "+" next to the field: create a category without leaving the form.
                        // Only the superadmin manages categories (TextureCategoryPolicy).
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nome da categoria')
                                ->required()
                                ->maxLength(100),
                        ])
                        ->createOptionUsing(fn(array $data) => TextureCategory::create([
                            'name' => $data['name'],
                            'slug' => SlugGenerator::unique(TextureCategory::class, $data['name']),
                        ])->getKey())
                        ->createOptionAction(fn(Action $action) => $action
                            ->modalHeading('Nova categoria de textura')
                            ->visible(fn() => auth()->user()->can('create', TextureCategory::class))),

                    TextEntry::make('user_id')
                        ->label('Enviado por')
                        ->state(fn(?Texture $record) => $record?->user?->name)
                        ->visible(fn(?Texture $record) => $record !== null),

                    TextEntry::make('version')
                        ->label('Versão dos mapas')
                        ->helperText('Sobe sozinha quando os mapas mudam; o plugin baixa de novo.')
                        ->visible(fn(?Texture $record) => $record !== null),

                    Textarea::make('description')
                        ->label('Descrição')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->disabled($locked),

            Section::make('Material')
                ->description('O tamanho real é o de uma repetição da imagem e vale para todas as resoluções.')
                ->columns(3)
                ->components([
                    Select::make('material_type')
                        ->label('Tipo de material')
                        ->options(Texture::MATERIAL_TYPES)
                        ->default(Texture::TYPE_PBR)
                        ->required()
                        ->live()
                        ->native(false),

                    TextInput::make('tile_width_mm')
                        ->label('Largura real da repetição')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(100000)
                        ->default(1000)
                        ->suffix('mm')
                        ->required(),

                    TextInput::make('tile_height_mm')
                        ->label('Altura real da repetição')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(100000)
                        ->default(1000)
                        ->suffix('mm')
                        ->required(),

                    ColorPicker::make('base_color')
                        ->label(fn(Get $get) => $get('material_type') === Texture::TYPE_PBR ? 'Cor de tingimento (opcional)' : 'Cor do material')
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->required(fn(Get $get) => $get('material_type') !== Texture::TYPE_PBR),

                    Toggle::make('is_seamless')
                        ->label('Sem emenda (seamless)')
                        ->default(true)
                        ->inline(false),

                    TextEntry::make('shading_color')
                        ->label('Cor na vista Sombreado')
                        ->helperText('Calculada a partir do mapa de cor.')
                        ->state(fn(?Texture $record) => $record?->shading_color ?? '—')
                        ->visible(fn(?Texture $record) => $record !== null),
                ])
                ->disabled($locked),

            Section::make('Mapas')
                ->description('Selecione todos os arquivos de uma vez. O tipo vem do nome (diffuse, normal, roughness, ao, displacement, alpha, metallic) e a resolução vem do tamanho em pixels.')
                ->components([
                    FileUpload::make('maps_upload')
                        ->label('Arquivos dos mapas')
                        ->multiple()
                        ->acceptedFileTypes(TextureMapService::MIME_TYPES)
                        ->maxSize(51200) // 50 MB per file, in KB
                        ->maxFiles(10)
                        // Raw uploads: TextureMapService decides names and folders.
                        ->storeFiles(false)
                        ->helperText('PNG ou JPG, até 50 MB cada.'),
                ])
                // On edit the maps are managed in the "Mapas" tab below.
                ->visible(fn(?Texture $record, Get $get) => $record === null && $get('material_type') === Texture::TYPE_PBR),

            Section::make('Capa')
                ->components([
                    FileUpload::make('cover_upload')
                        ->label('Imagem de capa (opcional)')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(5120)
                        ->disk('public')
                        ->visibility('public')
                        ->storeFiles(false)
                        ->helperText('Se ficar vazio, a capa é gerada a partir do mapa de cor.')
                        // Only a cover the admin uploaded shows here; the generated
                        // one is not "removable" because it follows the maps.
                        ->afterStateHydrated(function (FileUpload $component, ?Texture $record) {
                            $component->state(
                                $record && app(TextureMapService::class)->hasCustomCover($record)
                                    ? $record->preview_path
                                    : null
                            );
                        }),
                ])
                ->disabled($locked),

            Section::make('Acesso e destaque')
                ->columns(2)
                ->components([
                    Toggle::make('is_free')
                        ->label('Gratuita')
                        ->helperText('Desligado: só assinantes (qualquer plano).')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('is_featured')
                        ->label('Destaque')
                        ->inline(false),
                ])
                ->disabled($locked),

            Section::make('Publicação')
                ->components([
                    Select::make('status')
                        ->label('Status')
                        ->options(fn() => static::statusOptions())
                        ->default(Texture::STATUS_DRAFT)
                        ->required()
                        ->native(false),
                ])
                ->disabled($locked),

            Section::make('Motivo da rejeição')
                ->components([
                    TextEntry::make('rejection_reason_display')
                        ->label('Motivo')
                        ->state(fn(?Texture $record) => $record?->rejection_reason),
                ])
                ->visible(fn(?Texture $record) => $record !== null
                    && $record->isRejected()
                    && ! auth()->user()->isSuperAdmin()),

            Section::make('Aprovação')
                ->columns(2)
                ->components([
                    TextEntry::make('approved_by_name')
                        ->label('Aprovado/revisado por')
                        ->state(fn(?Texture $record) => $record?->approvedBy?->name ?? '—'),

                    TextEntry::make('approved_at_display')
                        ->label('Data da aprovação/revisão')
                        ->state(fn(?Texture $record) => $record?->approved_at?->format('d/m/Y H:i') ?? '—'),

                    Textarea::make('rejection_reason')
                        ->label('Motivo da rejeição')
                        ->columnSpanFull()
                        ->rows(3),
                ])
                ->visible(fn(?Texture $record) => $record !== null && auth()->user()->isSuperAdmin()),
        ]);
    }

    /** Moderators can only move a submission to draft or pending review. */
    protected static function statusOptions(): array
    {
        $options = [
            Texture::STATUS_DRAFT          => 'Rascunho',
            Texture::STATUS_PENDING_REVIEW => 'Aguardando revisão',
        ];

        if (auth()->user()->isSuperAdmin()) {
            $options[Texture::STATUS_PUBLISHED] = 'Publicado';
            $options[Texture::STATUS_REJECTED]  = 'Rejeitado';
        }

        return $options;
    }
}
