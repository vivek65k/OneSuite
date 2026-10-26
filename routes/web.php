<?php

use App\Http\Controllers\Admin\Crm\LandingPageController;
use App\Http\Controllers\Admin\Crm\WebFormController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->post('logout', [LoginController::class, 'destroy'])->name('logout');

// Deliberately outside the 'guest' middleware - an already-authenticated
// platform admin testing their own just-created invite link is a normal
// case, not one to bounce away. The 'signed' middleware is what actually
// gates this: only a valid, untampered, unexpired link gets through.
Route::match(['get', 'post'], 'invitation/{user}/accept', [InvitationController::class, 'accept'])
    ->middleware('signed')
    ->name('invitation.accept');

// Public-facing marketing pages - no auth, reachable by anonymous visitors.
Route::get('f/{slug}', [WebFormController::class, 'showPublic'])->name('crm.public.web-forms.show');
Route::post('f/{slug}', [WebFormController::class, 'submitPublic'])->name('crm.public.web-forms.submit');
Route::get('lp/{slug}', [LandingPageController::class, 'showPublic'])->name('crm.public.landing-pages.show');
