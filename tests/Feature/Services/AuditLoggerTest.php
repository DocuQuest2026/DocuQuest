<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;

it('records the actor, action, subject, metadata and request address', function () {
    $actor = User::factory()->admin()->create();
    $subject = User::factory()->staff()->create();

    $entry = app(AuditLogger::class)->log($actor, 'staff.created', $subject, ['role' => 'staff']);

    expect($entry->actor_id)->toBe($actor->id)
        ->and($entry->action)->toBe('staff.created')
        ->and($entry->subject_type)->toBe($subject->getMorphClass())
        ->and($entry->subject_id)->toBe($subject->id)
        ->and($entry->metadata)->toBe(['role' => 'staff'])
        ->and($entry->ip)->not->toBeNull();
});

it('records system actions that have no actor or subject', function () {
    $entry = app(AuditLogger::class)->log(null, 'system.keep_alive');

    expect($entry->actor_id)->toBeNull()
        ->and($entry->subject_type)->toBeNull()
        ->and($entry->metadata)->toBeNull();
});

it('rejects updates to an existing entry', function () {
    $entry = AuditLog::factory()->create(['action' => 'auth.login']);

    expect(fn () => $entry->update(['action' => 'auth.tampered']))
        ->toThrow(QueryException::class, 'audit_logs is append-only');
});

it('rejects deletion of an existing entry', function () {
    $entry = AuditLog::factory()->create();

    expect(fn () => $entry->delete())
        ->toThrow(QueryException::class, 'audit_logs is append-only');
});
