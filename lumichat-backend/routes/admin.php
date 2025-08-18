<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CounselorController;
use App\Http\Controllers\Admin\StudentController; // ⬅️ add this

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Counselors
        Route::resource('counselors', CounselorController::class)
            ->parameters(['counselors' => 'counselor']);

        // Students (index + show only)
        Route::resource('students', StudentController::class)
            ->only(['index', 'show'])
            ->parameters(['students' => 'student']); // binds to App\Models\Registration
    });
