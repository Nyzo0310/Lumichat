<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\SettingController;

Route::get('/', function () {
    return redirect()->route('chat.show');
});

// Protected routes
Route::middleware('auth')->group(function () {

    // Chat routes
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/api/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::post('/chat/greet', [ChatController::class, 'greet'])->name('chat.greet');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store'); // Used for saving messages
    Route::get('/chat/new', [ChatController::class, 'newChat'])->name('chat.new');

    // Chat History UI route (to be implemented)
    Route::get('/chat/history', [ChatController::class, 'history'])->name('chat.history');
    Route::delete('/chat/{id}', [ChatController::class, 'destroy'])->name('chat.delete');

    // Profile route
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Appointments page (optional)
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments');

    // Settings page (optional)
    Route::get('/settings', [SettingController::class, 'index'])->name('settings');

    // Logout is handled by POST form in Blade via Auth scaffolding
});
/* -----------------------------------------------------------------
|  Laravel Breeze / Fortify auth routes
|------------------------------------------------------------------*/
require __DIR__.'/auth.php';
