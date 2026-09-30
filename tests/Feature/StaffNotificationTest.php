<?php

use App\Models\RecordRequest;
use App\Models\User;
use App\Notifications\RecordRequestCancellationRequested;

test('a staff member can view a recent notification in the navigation', function () {
    $staff = User::factory()->staff()->create();
    $recordRequest = RecordRequest::factory()->create();
    $staff->notify(new RecordRequestCancellationRequested($recordRequest));

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertSee($recordRequest->reference_no);
});

test('opening a notification marks it read and redirects to the request', function () {
    $staff = User::factory()->staff()->create();
    $recordRequest = RecordRequest::factory()->create();
    $staff->notify(new RecordRequestCancellationRequested($recordRequest));
    $notification = $staff->notifications()->first();

    $this->actingAs($staff)
        ->post(route('notifications.read', $notification))
        ->assertRedirect(route('requests.show', $recordRequest));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('marking all notifications as read clears the unread count', function () {
    $staff = User::factory()->staff()->create();
    $staff->notify(new RecordRequestCancellationRequested(RecordRequest::factory()->create()));
    $staff->notify(new RecordRequestCancellationRequested(RecordRequest::factory()->create()));

    $this->actingAs($staff)->post(route('notifications.read-all'))->assertRedirect();

    expect($staff->unreadNotifications()->count())->toBe(0);
});

test('a staff member can not mark another user\'s notification as read', function () {
    $staff = User::factory()->staff()->create();
    $otherStaff = User::factory()->staff()->create();
    $otherStaff->notify(new RecordRequestCancellationRequested(RecordRequest::factory()->create()));
    $notification = $otherStaff->notifications()->first();

    $this->actingAs($staff)->post(route('notifications.read', $notification))->assertForbidden();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('guests are redirected to log in when managing notifications', function () {
    $staff = User::factory()->staff()->create();
    $staff->notify(new RecordRequestCancellationRequested(RecordRequest::factory()->create()));
    $notification = $staff->notifications()->first();

    $this->post(route('notifications.read', $notification))->assertRedirect(route('login'));
    $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
});
