<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CounselorController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\ChatbotSessionController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\SelfAssessmentController;

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
            ->parameters(['students' => 'student']);
          // Chatbot Sessions (read-only)
        Route::resource('chatbot-sessions', ChatbotSessionController::class)
            ->only(['index','show'])
            ->parameters(['chatbot-sessions' => 'session']);
        Route::get('/appointments', [AppointmentController::class, 'index'])
        ->name('appointments.index');

        Route::view('self-assessments', 'admin.self-assessments.index')
        ->name('self-assessments.index');
        Route::view('self-assessments/{id}', 'admin.self-assessments.show')
        ->name('self-assessments.show');

        // Diagnosis Reports (demo-friendly)
        Route::view('diagnosis-reports', 'admin.diagnosis-reports.index')
        ->name('diagnosis-reports.index');
        Route::view('diagnosis-reports/{id}', 'admin.diagnosis-reports.show')
        ->name('diagnosis-reports.show');

        Route::view('course-analytics', 'admin.course-analytics.index')
        ->name('course-analytics.index');
        Route::view('course-analytics/{id}', 'admin.course-analytics.show')
        ->name('course-analytics.show');

        
    });
