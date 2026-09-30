<?php

use App\Http\Controllers\Admin\AccountPasswordController;
use App\Http\Controllers\Admin\StaffAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordRequestCancellationController;
use App\Http\Controllers\RecordRequestController;
use App\Http\Controllers\Staff\DocumentReleaseController;
use App\Http\Controllers\Staff\StudentRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/request', [RecordRequestController::class, 'create'])->name('record-requests.create');
Route::post('/request', [RecordRequestController::class, 'store'])
    ->middleware('throttle:record-requests')
    ->name('record-requests.store');

Route::get('/request/cancel', [RecordRequestCancellationController::class, 'create'])->name('record-requests.cancel.create');
Route::post('/request/cancel', [RecordRequestCancellationController::class, 'sendLink'])
    ->middleware('throttle:record-request-cancellations')
    ->name('record-requests.cancel.send');
Route::middleware('signed')->group(function () {
    Route::get('/request/cancel/{recordRequest}', [RecordRequestCancellationController::class, 'show'])->name('record-requests.cancel.show');
    Route::post('/request/cancel/{recordRequest}', [RecordRequestCancellationController::class, 'store'])->name('record-requests.cancel.store');
    Route::get('/verify/{documentRelease}', [DocumentVerificationController::class, 'show'])->name('document-verification.show');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('requests', StudentRequestController::class)
        ->only(['index', 'show', 'destroy'])
        ->parameters(['requests' => 'recordRequest'])
        ->withTrashed(['show']);

    Route::post('/requests/{recordRequest}/approve', [StudentRequestController::class, 'approve'])->name('requests.approve');
    Route::post('/requests/{recordRequest}/reject', [StudentRequestController::class, 'reject'])->name('requests.reject');
    Route::post('/requests/{recordRequest}/confirm-cancellation', [StudentRequestController::class, 'confirmCancellation'])->name('requests.confirm-cancellation');
    Route::post('/requests/{recordRequest}/deny-cancellation', [StudentRequestController::class, 'denyCancellation'])->name('requests.deny-cancellation');
    Route::post('/requests/{recordRequest}/restore', [StudentRequestController::class, 'restore'])->name('requests.restore')->withTrashed();
    Route::get('/requests/{recordRequest}/release', [DocumentReleaseController::class, 'create'])->name('requests.release.create');
    Route::post('/requests/{recordRequest}/release', [DocumentReleaseController::class, 'store'])->name('requests.release.store');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('staff', StaffAccountController::class)
        ->parameters(['staff' => 'user'])
        ->except(['show']);

    Route::post('staff/{user}/restore', [StaffAccountController::class, 'restore'])->name('staff.restore')->withTrashed();
    Route::delete('staff/{user}/force-delete', [StaffAccountController::class, 'forceDelete'])->name('staff.force-delete')->withTrashed();

    Route::put('accounts/{user}/password', [AccountPasswordController::class, 'update'])->name('accounts.password.update');
});

require __DIR__.'/auth.php';
