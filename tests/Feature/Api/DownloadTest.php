<?php

use App\Models\Family;
use App\Models\FamilyPurchase;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->user = User::factory()->create();
    signIn();
});

it('downloads a free family and records it', function () {
    $family = attachRfaFile(makeFamily());

    $this->get("/api/v1/families/{$family->slug}/download")
        ->assertOk()
        ->assertDownload("{$family->slug}.rfa");

    $this->assertDatabaseHas('downloads', [
        'user_id'   => $this->user->id,
        'family_id' => $family->id,
    ]);

    expect($family->fresh()->download_count)->toBe(1);
});

it('denies a paid family without access and records nothing', function () {
    $family = attachRfaFile(makeFamily(['is_free' => false, 'price' => 80]));

    $this->getJson("/api/v1/families/{$family->slug}/download")
        ->assertForbidden()
        ->assertJsonStructure(['message']);

    $this->assertDatabaseCount('downloads', 0);
    expect($family->fresh()->download_count)->toBe(0);
});

it('allows a paid family for a subscriber', function () {
    subscribe($this->user, makePlan(['max_downloads_per_month' => null]));

    $family = attachRfaFile(makeFamily(['is_free' => false, 'price' => 80]));

    $this->get("/api/v1/families/{$family->slug}/download")
        ->assertOk()
        ->assertDownload("{$family->slug}.rfa");
});

it('enforces the monthly quota but lets the same family be downloaded again', function () {
    subscribe($this->user, makePlan(['max_downloads_per_month' => 1]));

    $first  = attachRfaFile(makeFamily(['is_free' => false, 'price' => 10]));
    $second = attachRfaFile(makeFamily(['is_free' => false, 'price' => 20]));

    // Uses the only slot of the month.
    $this->get("/api/v1/families/{$first->slug}/download")->assertOk();

    // Another paid family is over the limit.
    $this->getJson("/api/v1/families/{$second->slug}/download")->assertForbidden();

    // The same family again never uses a new slot.
    $this->get("/api/v1/families/{$first->slug}/download")->assertOk();
});

it('allows a purchased family without a subscription', function () {
    $family = attachRfaFile(makeFamily(['is_free' => false, 'price' => 10]));

    FamilyPurchase::create([
        'user_id'   => $this->user->id,
        'family_id' => $family->id,
        'amount'    => 10,
        'status'    => 'paid',
        'paid_at'   => now(),
    ]);

    $this->get("/api/v1/families/{$family->slug}/download")->assertOk();
});

it('answers 404 for unpublished families and missing files without recording anything', function () {
    $draft = attachRfaFile(makeFamily(['status' => Family::STATUS_DRAFT]));
    $this->getJson("/api/v1/families/{$draft->slug}/download")->assertNotFound();

    $withoutRow = makeFamily();
    $this->getJson("/api/v1/families/{$withoutRow->slug}/download")->assertNotFound();

    $missing = attachRfaFile(makeFamily());
    Storage::disk('local')->delete("families/rfa/{$missing->slug}.rfa");
    $this->getJson("/api/v1/families/{$missing->slug}/download")->assertNotFound();

    $this->assertDatabaseCount('downloads', 0);
});