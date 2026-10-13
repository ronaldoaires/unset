<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanForm
{
    /**
     * Keys stored in features.modules. A paid plan unlocks the whole module,
     * there are no basic/advanced tiers. Checked through Plan::hasModule()
     * and User::hasAccess().
     */
    protected static function moduleOptions(): array
    {
        return [
            'architectural' => 'Arquitetônico',
            'structural'    => 'Estrutural',
        ];
    }

    /**
     * Short explanation shown under each module checkbox so the admin
     * knows exactly what the module includes.
     */
    protected static function moduleDescriptions(): array
    {
        return [
            'architectural' => 'Ferramentas de arquitetura e plugin de famílias.',
            'structural'    => 'Ferramentas de engenharia estrutural.',
        ];
    }

    /**
     * Keys stored in features.permissions. Only for account interactions
     * that differ between plans. Favorites and ratings may end up open to
     * every logged-in user instead (see the note on User::hasAccess()).
     */
    protected static function permissionOptions(): array
    {
        return [
            'can_favorite' => 'Favoritar famílias',
            'can_rate'     => 'Avaliar famílias',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Informações básicas')
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('badge_label')
                        ->label('Selo')
                        ->helperText('Texto curto exibido no card do plano (ex: Mais popular).')
                        ->maxLength(50),

                    Textarea::make('description')
                        ->label('Descrição')
                        ->columnSpanFull()
                        ->rows(3),
                ]),

            Section::make('Preços e cobrança')
                ->columns(2)
                ->components([
                    TextInput::make('price_monthly')
                        ->label('Preço mensal')
                        ->numeric()
                        ->inputMode('decimal')
                        ->prefix('R$')
                        ->minValue(0)
                        ->default(0)
                        ->required(),

                    TextInput::make('price_yearly')
                        ->label('Preço anual')
                        ->numeric()
                        ->inputMode('decimal')
                        ->prefix('R$')
                        ->minValue(0)
                        ->default(0)
                        ->required(),

                    TextInput::make('mp_plan_id_monthly')
                        ->label('ID do plano mensal (Mercado Pago)')
                        ->maxLength(100),

                    TextInput::make('mp_plan_id_yearly')
                        ->label('ID do plano anual (Mercado Pago)')
                        ->maxLength(100),

                    TextInput::make('trial_days')
                        ->label('Dias de teste grátis')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),

                    TextInput::make('max_downloads_per_month')
                        ->label('Downloads por mês')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Deixe vazio para downloads ilimitados.'),
                ]),

            Section::make('Módulos incluídos')
                ->description('Quem assina este plano tem acesso completo aos módulos marcados.')
                ->components([
                    // Dot notation writes into the "features" JSON column.
                    CheckboxList::make('features.modules')
                        ->label('Módulos')
                        ->options(fn () => static::moduleOptions())
                        ->descriptions(fn () => static::moduleDescriptions())
                        ->columns(2),
                ]),

            Section::make('Benefícios')
                ->columns(3)
                ->components([
                    Toggle::make('has_api_access')
                        ->label('Acesso à API')
                        ->inline(false),

                    Toggle::make('has_priority_support')
                        ->label('Suporte prioritário')
                        ->inline(false),

                    Toggle::make('has_exclusive_content')
                        ->label('Conteúdo exclusivo')
                        ->inline(false),
                ]),

            Section::make('Permissões')
                ->components([
                    CheckboxList::make('features.permissions')
                        ->label('Permissões do plano')
                        ->options(fn () => static::permissionOptions())
                        ->columns(2),
                ]),

            Section::make('Exibição')
                ->columns(3)
                ->components([
                    TextInput::make('sort_order')
                        ->label('Ordem de exibição')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    Toggle::make('is_highlighted')
                        ->label('Destaque')
                        ->helperText('Card do plano em destaque na página de preços.')
                        ->inline(false),

                    Toggle::make('is_active')
                        ->label('Ativo')
                        ->default(true)
                        ->inline(false),
                ]),
        ]);
    }
}