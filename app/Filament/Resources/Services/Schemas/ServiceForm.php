<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Enums\ServiceArea;
use App\Filament\Support\ContentEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Informações')
                ->columns(2)
                ->components([
                    Select::make('area')
                        ->label('Área')
                        ->options(ServiceArea::class)
                        ->required()
                        ->native(false),

                    TextInput::make('name')
                        ->label('Nome')
                        ->placeholder('Ex: Projeto Estrutural')
                        ->required()
                        ->maxLength(120),

                    TextInput::make('headline')
                        ->label('Chamada principal')
                        ->placeholder('Ex: Estrutura segura e econômica, do cálculo ao detalhamento')
                        ->helperText('Frase de impacto no topo da página do serviço.')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('summary')
                        ->label('Resumo')
                        ->helperText('Aparece nos cards e no Google quando o campo de SEO estiver vazio. Até 300 caracteres.')
                        ->required()
                        ->maxLength(300)
                        ->rows(3)
                        ->columnSpanFull(),

                    TextInput::make('icon')
                        ->label('Ícone')
                        ->placeholder('Ex: building-office-2')
                        ->helperText('Nome de um ícone Heroicons (outline), sem o prefixo.')
                        ->maxLength(60),
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
                        ->helperText('PNG, JPG ou WebP, até 5 MB. Também é usada ao compartilhar o link.'),
                ]),

            Section::make('Conteúdo da página')
                ->components([
                    ContentEditor::make('body', 'services/content')
                        ->label('Texto')
                        ->helperText('Explique o problema que o serviço resolve, para quem é e como funciona.'),
                ]),

            Section::make('O que está incluso')
                ->components([
                    Repeater::make('deliverables')
                        ->label('Itens')
                        ->simple(
                            TextInput::make('item')
                                ->placeholder('Ex: Planta de formas')
                                ->required()
                                ->maxLength(150),
                        )
                        ->addActionLabel('Adicionar item')
                        ->reorderable()
                        ->defaultItems(0),
                ]),

            Section::make('Perguntas frequentes')
                ->description('Também viram resultado rico no Google (FAQ).')
                ->collapsible()
                ->components([
                    Repeater::make('faq')
                        ->label('Perguntas')
                        ->schema([
                            TextInput::make('question')
                                ->label('Pergunta')
                                ->required()
                                ->maxLength(200),

                            Textarea::make('answer')
                                ->label('Resposta')
                                ->required()
                                ->maxLength(1000)
                                ->rows(3),
                        ])
                        ->itemLabel(fn (array $state) => $state['question'] ?? null)
                        ->addActionLabel('Adicionar pergunta')
                        ->collapsible()
                        ->reorderable()
                        ->defaultItems(0),
                ]),

            Section::make('SEO')
                ->description('Opcional. Em branco, usa o nome e o resumo.')
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
                        ->helperText('Só ative depois de preencher o conteúdo.')
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
}
