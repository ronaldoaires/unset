<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Lead;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Contato')
                ->columns(2)
                ->components([
                    TextEntry::make('name')->label('Nome'),

                    TextEntry::make('email')
                        ->label('E-mail')
                        ->copyable()
                        ->url(fn (Lead $record) => 'mailto:' . $record->email),

                    TextEntry::make('phone')
                        ->label('WhatsApp')
                        ->placeholder('—')
                        ->copyable()
                        ->url(fn (Lead $record) => $record->whatsappUrl())
                        ->openUrlInNewTab(),

                    TextEntry::make('city')
                        ->label('Cidade')
                        ->state(fn (Lead $record) => collect([$record->city, $record->state])->filter()->join('/') ?: null)
                        ->placeholder('—'),
                ]),

            Section::make('Pedido')
                ->columns(2)
                ->components([
                    TextEntry::make('type')->label('Tipo')->badge(),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('service.name')->label('Serviço')->placeholder('—'),
                    TextEntry::make('project.title')->label('Veio do projeto')->placeholder('—'),

                    TextEntry::make('message')
                        ->label('Mensagem')
                        ->columnSpanFull()
                        ->prose(),

                    KeyValueEntry::make('details')
                        ->label('Detalhes')
                        ->keyLabel('Pergunta')
                        ->valueLabel('Resposta')
                        ->columnSpanFull()
                        ->visible(fn (Lead $record) => filled($record->details)),

                    TextEntry::make('notes')
                        ->label('Anotações internas')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make('Origem')
                ->collapsible()
                ->collapsed()
                ->columns(2)
                ->components([
                    TextEntry::make('created_at')->label('Recebido em')->dateTime('d/m/Y H:i'),
                    TextEntry::make('source_url')->label('Página')->placeholder('—'),
                    TextEntry::make('utm_source')->label('UTM source')->placeholder('—'),
                    TextEntry::make('utm_medium')->label('UTM medium')->placeholder('—'),
                    TextEntry::make('utm_campaign')->label('UTM campaign')->placeholder('—'),
                    TextEntry::make('user.name')->label('Usuário cadastrado')->placeholder('Não estava logado'),
                    TextEntry::make('privacy_accepted_at')->label('Aceitou a privacidade em')->dateTime('d/m/Y H:i')->placeholder('—'),
                    TextEntry::make('ip')->label('IP')->placeholder('—'),
                ]),
        ]);
    }
}
