<?php

use App\Models\Service;
use Database\Seeders\ServiceSeeder;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Default services (database/seeders/ServiceSeeder.php)
|--------------------------------------------------------------------------
*/

beforeEach(fn () => Storage::fake('public'));

it('creates the seven default services with covers', function () {
    $this->seed(ServiceSeeder::class);

    expect(Service::count())->toBe(7)
        ->and(Service::where('is_active', true)->count())->toBe(7)
        ->and(ServiceSeeder::missing())->toBe([]);

    Service::all()->each(function (Service $service) {
        expect($service->cover_path)->not->toBeNull();
        Storage::disk('public')->assertExists($service->cover_path);
    });
});

it('keeps the SEO fields within the limits of the admin form', function () {
    foreach (ServiceSeeder::services() as $service) {
        expect(mb_strlen($service['seo_title']))->toBeLessThanOrEqual(70, $service['slug'])
            ->and(mb_strlen($service['seo_description']))->toBeLessThanOrEqual(160, $service['slug'])
            ->and(mb_strlen($service['summary']))->toBeLessThanOrEqual(300, $service['slug'])
            ->and(is_file(base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg/o-' . $service['icon'] . '.svg')))->toBeTrue($service['icon']);
    }
});

it('never overwrites a service edited in the admin', function () {
    $this->seed(ServiceSeeder::class);

    Service::where('slug', 'projeto-estrutural')->update(['headline' => 'Texto editado no painel']);
    Service::where('slug', 'projeto-eletrico')->delete();

    expect(ServiceSeeder::missing())->toBe(['projeto-eletrico']);

    $this->seed(ServiceSeeder::class);

    expect(Service::count())->toBe(7)
        ->and(Service::where('slug', 'projeto-estrutural')->value('headline'))->toBe('Texto editado no painel');
});

it('renders every seeded service page with its content and FAQ', function () {
    $this->seed(ServiceSeeder::class);

    foreach (Service::all() as $service) {
        $this->get(route('services.show', ['area' => $service->area->slug(), 'service' => $service->slug]))
            ->assertOk()
            ->assertSee($service->seo_title)
            ->assertSee('<h2>', false)
            ->assertSee('"@type":"FAQPage"', false);
    }
});
