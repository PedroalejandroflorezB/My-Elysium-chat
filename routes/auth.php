<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\AdminRecoveryController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [AdminRecoveryController::class, 'showRecoveryForm'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    // Admin Recovery Routes
    Route::post('admin/recovery/verify', [AdminRecoveryController::class, 'verifyRecoveryCode'])
        ->name('admin.recovery.verify');

    Route::get('admin/recovery/reset', [AdminRecoveryController::class, 'showResetForm'])
        ->name('admin.recovery.reset.form');

    Route::post('admin/recovery/reset', [AdminRecoveryController::class, 'resetPassword'])
        ->name('admin.recovery.reset');

    // Guardar códigos desde el panel admin (requiere auth - FUERA del grupo guest)
    Route::post('user/gmail/verify', [AdminRecoveryController::class, 'verifyGmailCode'])
        ->name('user.gmail.verify');

    Route::post('user/gmail/send', [AdminRecoveryController::class, 'sendGmailCode'])
        ->name('user.gmail.send');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Guardar códigos de recuperación admin (requiere auth)
    Route::post('admin/recovery/save-codes', [AdminRecoveryController::class, 'saveRecoveryCodes'])
        ->name('admin.recovery.save-codes');
});
