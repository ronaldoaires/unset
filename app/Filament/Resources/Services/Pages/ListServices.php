<?php

namespace App\Filament\Resources\Services\Pages;

use App\Enums\ServiceArea;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use Database\Seeders\ServiceSeeder;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No terminal on the shared hosting: the default services are
            // created from here. Only missing ones are added; edited ones are kept.
            Action::make('seedDefaults')
                ->label('Criar serviços padrão')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->visible(fn () => ServiceSeeder::missing() !== [])
                ->requiresConfirmation()
                ->modalHeading('Criar serviços padrão')
                ->modalDescription(fn () => 'Serão criados, já ativos e com texto e capa: ' . implode(', ', ServiceSeeder::missing()) . '. Serviços que já existem não são alterados.')
                ->action(function () {
                    $count = count(ServiceSeeder::missing());

                    (new ServiceSeeder())->run();

                    Notification::make()
                        ->title($count . ' serviço(s) criado(s)')
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }

    /**
     * One tab per area, so engineering and technology stay apart without
     * two separate resources.
     */
    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('Todos')];

        foreach (ServiceArea::cases() as $area) {
            $tabs[$area->value] = Tab::make($area->getLabel())
                ->badge(Service::query()->inArea($area)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->inArea($area));
        }

        return $tabs;
    }
}
