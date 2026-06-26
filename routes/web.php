<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register/send-code', [AuthController::class, 'sendRegisterAccessCode'])
        ->middleware('throttle:3,1')
        ->name('register.access.send');
    Route::post('/register/verify-code', [AuthController::class, 'verifyRegisterAccessCode'])
        ->middleware('throttle:10,1')
        ->name('register.access.verify');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/password-reset', [AuthController::class, 'showPasswordReset'])->name('password.request');
    Route::post('/password-reset/send-code', [AuthController::class, 'sendPasswordResetAccessCode'])
        ->middleware('throttle:3,1')
        ->name('password.access.send');
    Route::post('/password-reset/verify-code', [AuthController::class, 'verifyPasswordResetAccessCode'])
        ->middleware('throttle:10,1')
        ->name('password.access.verify');
    Route::post('/password-reset', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('students.index');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::resource('students', StudentController::class)->except(['show']);
    Route::post('/students-export', [StudentController::class, 'export'])->name('students.export');
    Route::post('/students-upload', [StudentController::class, 'upload'])->name('students.upload');
});
