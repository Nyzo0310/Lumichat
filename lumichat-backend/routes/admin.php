<?php

use Illuminate\Support\Facades\Route;

// Auth controller for admin login form (same controller you use for student login)
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Admin controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CounselorController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\ChatbotSessionController;
use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;

/*
|--------------------------------------------------------------------------
| Public (guest) admin auth routes
| - These MUST come before the protected admin group
| - They render the same login view but with admin context
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware('guest')
    ->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.post');
    });

/*
|--------------------------------------------------------------------------
| Protected admin routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {

        /* ===================== DASHBOARD ===================== */
        // Main dashboard page
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // JSON stats endpoint (polled by the dashboard)
        // URL => /admin/dashboard/stats , name => admin.dashboard.stats
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        /* ===================== COUNSELORS ===================== */
        Route::resource('counselors', CounselorController::class)
            ->parameters(['counselors' => 'counselor']);

        /* ===================== STUDENTS (read-only) ===================== */
        Route::resource('students', StudentController::class)
            ->only(['index', 'show'])
            ->parameters(['students' => 'student']);

        /* ===================== CHATBOT SESSIONS (read-only) ===================== */
        Route::resource('chatbot-sessions', ChatbotSessionController::class)
            ->only(['index', 'show'])
            ->parameters(['chatbot-sessions' => 'session']);

        /* ===================== APPOINTMENTS (Admin) ===================== */
        Route::get('/appointments', [AdminAppointmentController::class, 'index'])
            ->name('appointments.index');

        Route::get('/appointments/{appointment}', [AdminAppointmentController::class, 'show'])
            ->name('appointments.show');

        // Update status via AJAX/normal PATCH
        Route::patch('/appointments/{appointment}/status', [AdminAppointmentController::class, 'updateStatus'])
            ->name('appointments.status');

        /* ===================== REPORTS / ANALYTICS (static views) ===================== */
        Route::view('self-assessments',        'admin.self-assessments.index')->name('self-assessments.index');
        Route::view('self-assessments/{id}',   'admin.self-assessments.show')->name('self-assessments.show');

        Route::view('diagnosis-reports',       'admin.diagnosis-reports.index')->name('diagnosis-reports.index');
        Route::view('diagnosis-reports/{id}',  'admin.diagnosis-reports.show')->name('diagnosis-reports.show');

        Route::view('course-analytics',        'admin.course-analytics.index')->name('course-analytics.index');
        Route::view('course-analytics/{id}',   'admin.course-analytics.show')->name('course-analytics.show');
    });
