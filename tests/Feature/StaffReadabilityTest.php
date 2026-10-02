<?php

use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use App\Models\User;

test('the staff pages have no text size switcher', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Text size')
        ->assertDontSee('docuquest-text-size');

    $this->actingAs($staff)->get(route('requests.index'))
        ->assertOk()
        ->assertDontSee('Larger text size');
});

test('the dashboard uses plain words for what each count means', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('At a glance')
        ->assertSee('Waiting for approval')
        ->assertSee('Ready to release')
        ->assertSee('Students asking to cancel')
        ->assertSee('Ready for pickup')
        ->assertSee('The oldest are at the top, so you can work from the top down.');
});

test('each request on the dashboard has one large, clearly labelled button to open it', function () {
    $pending = RecordRequest::factory()->create(['status' => RequestStatus::Pending]);
    $approved = RecordRequest::factory()->approved()->create();

    $html = $this->actingAs(User::factory()->staff()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Open request')
        ->assertSee(route('requests.show', $pending), false)
        ->assertSee(route('requests.show', $approved), false)
        ->getContent();

    expect(substr_count($html, 'Open request'))->toBe(2);
});

test('the dashboard keeps its detail text readable: no faint gray and no tiny type', function () {
    RecordRequest::factory()->create(['status' => RequestStatus::Pending]);

    $html = $this->actingAs(User::factory()->staff()->create())->get(route('dashboard'))->assertOk()->getContent();

    $main = substr($html, strpos($html, '<main'), strpos($html, '</main>') - strpos($html, '<main'));

    expect($main)->not->toContain('text-[10px]')
        ->and($main)->not->toContain('text-gray-400')
        ->and($main)->not->toContain('text-[11px]');
});

test('the full navigation appears from tablet width, with a menu button on phones', function () {
    $this->actingAs(User::factory()->staff()->create())->get(route('dashboard'))
        ->assertOk()
        ->assertSeeHtml('hidden items-center gap-1 md:flex')
        ->assertSeeHtml('aria-label="Menu"');
});
