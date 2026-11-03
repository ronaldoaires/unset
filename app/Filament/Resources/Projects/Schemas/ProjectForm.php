<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ServiceArea;
use App\Filament\Support\ContentEditor;
use App\Models\Service;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Informações')
                ->columns(2)
                ->components([
                    TextInput::make('title')
                        ->label('Título')
                        ->placeholder('Ex: Projeto estrutural de sobrado 10x16')
                        ->required()
                        ->maxLength(150),

                    Select::make('service_id')
                        ->label('Serviço')
                        ->options(fn () => static::serviceOptions())
                        ->searchable()
                        ->required(),

                    Textarea::make('summary')
                        ->label('Resumo')
                        ->helperText('Aparece nos cards e no Google quando o campo de SEO estiver vazio. Até 300 caracteres.')
                        ->required()
                        ->maxLength(300)
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Dados do projeto')
                ->description('Tudo opcional, mas ajuda a passar confiança.')
                ->columns(3)
                ->components([
                    TextInput::make('location')
                        ->label('Local')
                        ->placeholder('Ex: Nova Xavantina/MT')
                        ->maxLength(120),

                    TextInput::make('year')
                        ->label('Ano')
                        ->numeric()
                        ->minValue(1990)
                        ->maxValue((int) now()->format('Y') + 1),

                    TextInput::make('built_area')
                        ->label('Área construída')
                        ->numeric()
                        ->inputMode('decimal')
                        ->minValue(0)
                        ->suffix('m²'),

                    TextInput::make('external_url')
                        ->label('Link do projeto no ar')
                        ->helperText('Para sites e apps.')
                        ->url()
                        ->maxLength(255),

                    TextInput::make('client_name')
                        ->label('Cliente')
                        ->helperText('Só preencha com autorização do cliente.')
                        ->maxLength(150),
                ]),

            Section::make('Imagem de capa')
                ->components([
                    // Kept as a raw upload (storeFiles(false)) so the Create/Edit
                    // pages can run it through ImageService (webp + thumbnail).
                    FileUpload::make('cover_path')
                        ->label('Capa')
                        ->image()
                        ->imageEditor()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(5120)
                        ->disk('public')
                        ->visibility('public')
                        ->storeFiles(false)
                        ->required()
                        ->helperText('PNG, JPG ou WebP, até 5 MB. As demais fotos vão na galeria, depois de salvar.'),
                ]),

            Section::make('Descrição')
                ->components([
                    ContentEditor::make('body', 'projects/content')
                        ->label('Texto')
                        ->helperText('Conte o desafio, a solução e o resultado.'),
                ]),

            Section::make('SEO')
                ->description('Opcional. Em branco, usa o título e o resumo.')
                ->collapsible()
                ->collapsed()
                ->columns(2)
                ->components([
                    TextInput::make('seo_title')
                        ->label('Título no Google')
                        ->maxLength(70)
                        ->helperText('Até 70 caracteres.'),

                    Textarea::make('seo_description')
                        ->label('Descrição no Google')
                        ->maxLength(160)
                        ->rows(2)
                        ->helperText('Até 160 caracteres.'),
                ]),

            Section::make('Publicação')
                ->columns(3)
                ->components([
                    Toggle::make('is_active')
                        ->label('Ativo no site')
                        ->inline(false),

                    Toggle::make('is_featured')
                        ->label('Destaque na home')
                        ->inline(false),

                    TextInput::make('sort_order')
                        ->label('Posição')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Menor aparece primeiro.'),
                ]),
        ]);
    }

    /**
     * Services grouped by area in the select.
     *
     * @return array<string, array<int, string>>
     */
    protected static function serviceOptions(): array
    {
        $options = [];

        foreach (ServiceArea::cases() as $area) {
            $services = Service::query()
                ->inArea($area)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();

            if ($services) {
                $options[$area->getLabel()] = $services;
            }
        }

        return $options;
    }
}
