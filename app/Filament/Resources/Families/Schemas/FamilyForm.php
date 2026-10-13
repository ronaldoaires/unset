<?php

namespace App\Filament\Resources\Families\Schemas;

use App\Filament\Resources\Families\FamilyResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Family;
use App\Models\Subcategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;

class FamilyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Informações básicas')
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),

                    Select::make('category_id')
                        ->label('Categoria')
                        ->options(fn() => Category::query()->active()->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn(Set $set) => $set('subcategory_id', null)),

                    Select::make('subcategory_id')
                        ->label('Subcategoria')
                        ->options(
                            fn(Get $get) => Subcategory::query()
                                ->where('category_id', $get('category_id'))
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->disabled(fn(Get $get) => blank($get('category_id')))
                        ->helperText('Opcional. Escolha a categoria primeiro.'),

                    Select::make('brand_id')
                        ->label('Marca')
                        ->options(fn() => Brand::query()->active()->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),

                    TextEntry::make('user_id')
                        ->label('Enviado por')
                        ->state(fn(?Family $record) => $record?->user?->name ?? auth()->user()->name)
                        ->visible(fn(?Family $record) => $record !== null),

                    Textarea::make('description')
                        ->label('Descrição')
                        ->columnSpanFull()
                        ->rows(4),
                ])
                ->disabled(fn(?Family $record) => FamilyResource::isLocked($record)),

            Section::make('Arquivos')
                ->columns(2)
                ->components([
                    FileUpload::make('rfa_upload')
                        ->label('Arquivo Revit (.rfa)')
                        // Private disk: the .rfa is the paid product and must only be
                        // served through the authorized download route.
                        ->disk('local')
                        ->directory('families/rfa')
                        ->visibility('private')
                        // No acceptedFileTypes() on purpose: .rfa has no MIME type the OS
                        // knows, so any "accept" filter hides every .rfa file in the picker.
                        // The extension is validated server-side instead.
                        ->rules([
                            fn() => function (string $attribute, mixed $value, \Closure $fail) {
                                $name = match (true) {
                                    $value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                                    => $value->getClientOriginalName(),
                                    is_string($value) => $value, // existing stored path on edit
                                    default           => '',
                                };

                                if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'rfa') {
                                    $fail('O arquivo deve ter a extensão .rfa.');
                                }
                            },
                        ])
                        ->maxSize(51200) // 50 MB, in KB
                        ->helperText('Arquivo único de download da família (.rfa, até 50 MB).')
                        ->required(fn(?Family $record) => $record === null)
                        ->downloadable()
                        ->openable(false)
                        ->afterStateHydrated(
                            fn(FileUpload $component, ?Family $record) => $component->state($record?->rfaFiles->first()?->path)
                        ),

                    FileUpload::make('cover_image_upload')
                        ->label('Imagem de capa')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(5120) // 5 MB, in KB
                        ->imageEditor()
                        ->disk('public')
                        ->directory('families/images')
                        ->visibility('public')
                        // Required on edit too: the field is hydrated with the stored path, so
                        // saving untouched passes, but removing the cover without uploading a
                        // new one is blocked.
                        ->required()
                        // Kept as a raw upload (not auto-stored) so CreateFamily/EditFamily
                        // can run it through ImageService (webp + thumbnail).
                        ->storeFiles(false)
                        ->afterStateHydrated(
                            fn(FileUpload $component, ?Family $record) => $component->state($record?->primaryImage?->path)
                        ),
                ])
                ->disabled(fn(?Family $record) => FamilyResource::isLocked($record)),

            Section::make('Atributos Revit')
                ->columns(3)
                ->components([
                    TextInput::make('revit_version')
                        ->label('Versão do Revit')
                        ->numeric()
                        ->required()
                        ->minValue(2025),

                    TextInput::make('revit_category')
                        ->label('Categoria do Revit')
                        ->helperText('Ex: Doors, Windows, Furniture')
                        ->maxLength(255),

                    Toggle::make('is_parametric')
                        ->label('Paramétrica')
                        ->helperText('A família possui parâmetros editáveis no Revit')
                        ->inline(false),
                ])
                ->disabled(fn(?Family $record) => FamilyResource::isLocked($record)),

            Section::make('Preço e destaque')
                ->columns(3)
                ->components([
                    Toggle::make('is_free')
                        ->label('Gratuita')
                        ->live()
                        ->default(true)
                        ->inline(false),

                    TextInput::make('price')
                        ->label('Preço')
                        ->numeric()
                        ->inputMode('decimal')
                        ->prefix('R$')
                        ->minValue(0)
                        ->visible(fn(Get $get) => ! $get('is_free'))
                        ->required(fn(Get $get) => ! $get('is_free')),

                    Toggle::make('is_featured')
                        ->label('Destaque')
                        ->helperText('Aparece em destaque na home e listagens')
                        ->inline(false),
                ])
                ->disabled(fn(?Family $record) => FamilyResource::isLocked($record)),

            Section::make('Tags')
                ->components([
                    TagsInput::make('tag_list')
                        ->nestedRecursiveRules(['max:80'])
                        ->label('Tags')
                        ->placeholder('Digite e pressione Enter')
                        // No separator() on purpose: with one, the state becomes a
                        // comma-separated string, but syncTags() expects an array.
                        // Not a Family column: the Create/Edit pages strip it from the
                        // model data and sync it into family_tags after saving.
                        ->afterStateHydrated(
                            fn(TagsInput $component, ?Family $record) => $component->state($record?->tag_list ?? [])
                        ),
                ])
                ->disabled(fn(?Family $record) => FamilyResource::isLocked($record)),

            Section::make('Publicação')
                ->columns(2)
                ->components([
                    Select::make('status')
                        ->label('Status')
                        ->options(fn() => static::statusOptions())
                        ->default(Family::STATUS_DRAFT)
                        ->required()
                        ->native(false),
                ])
                ->disabled(fn(?Family $record) => FamilyResource::isLocked($record)),

            Section::make('Motivo da rejeição')
                ->components([
                    TextEntry::make('rejection_reason_display')
                        ->label('Motivo')
                        ->state(fn(?Family $record) => $record?->rejection_reason),
                ])
                // Moderators only see this while the family is rejected.
                ->visible(fn(?Family $record) => $record !== null
                    && $record->isRejected()
                    && ! auth()->user()->isSuperAdmin()),

            Section::make('Aprovação')
                ->columns(2)
                ->components([
                    TextEntry::make('approved_by_name')
                        ->label('Aprovado/revisado por')
                        ->state(fn(?Family $record) => $record?->approvedBy?->name ?? '—'),

                    TextEntry::make('approved_at_display')
                        ->label('Data da aprovação/revisão')
                        ->state(fn(?Family $record) => $record?->approved_at?->format('d/m/Y H:i') ?? '—'),

                    Textarea::make('rejection_reason')
                        ->label('Motivo da rejeição')
                        ->columnSpanFull()
                        ->rows(3),
                ])
                // Only the superadmin sees/edits this section, and only on the edit
                // page: a family that doesn't exist yet has nothing to approve.
                // Moderators never interact with approval fields directly, only
                // through the "Aprovar"/"Rejeitar" table actions.
                ->visible(fn(?Family $record) => $record !== null && auth()->user()->isSuperAdmin()),

        ]);
    }

    /**
     * Moderators may only push a submission into draft or pending review.
     * Only the superadmin can move it to published or rejected — in
     * practice that happens through the dedicated Approve/Reject actions,
     * but we still keep this select consistent for direct edits.
     */
    protected static function statusOptions(): array
    {
        $options = [
            Family::STATUS_DRAFT          => 'Rascunho',
            Family::STATUS_PENDING_REVIEW => 'Aguardando revisão',
        ];

        if (auth()->user()->isSuperAdmin()) {
            $options[Family::STATUS_PUBLISHED] = 'Publicado';
            $options[Family::STATUS_REJECTED]  = 'Rejeitado';
        }

        return $options;
    }
}
