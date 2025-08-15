<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\SettingsController;

// Default redirect
Route::get('/', function () {
    return redirect()->route('chat.index');
});

// ✅ Registration routes — accessible to guests
Route::get('/register', [RegisteredUserController::class, 'create'])
    ->middleware('guest')
    ->name('register');

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest');

// ✅ Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/new', [ChatController::class, 'newChat'])->name('chat.new');
    Route::get('/chat/history', [ChatController::class, 'history'])->name('chat.history');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/view/{id}', [ChatController::class, 'viewSession'])->name('chat.view');
    Route::delete('/chat/session/{id}', [ChatController::class, 'deleteSession'])->name('chat.deleteSession');
    Route::delete('/chat/bulk-delete', [ChatController::class, 'bulkDelete'])->name('chat.bulkDelete');

    Route::post('/chat/activate/{id}', [ChatController::class, 'activate'])
    ->name('chat.activate');


    // Profile
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy.policy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
// ✅ Default auth routes (login, logout, etc.)
require __DIR__.'/auth.php';
