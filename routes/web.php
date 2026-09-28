<?php

use App\Http\Controllers\Admin\AccountPasswordController;
use App\Http\Controllers\Admin\StaffAccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordRequestCancellationController;
use App\Http\Controllers\RecordRequestController;
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
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('requests', StudentRequestController::class)
        ->only(['index', 'show'])
        ->parameters(['requests' => 'recordRequest']);
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('staff', StaffAccountController::class)
        ->parameters(['staff' => 'user'])
        ->except(['show', 'destroy']);

    Route::put('accounts/{user}/password', [AccountPasswordController::class, 'update'])->name('accounts.password.update');
});

require __DIR__.'/auth.php';
