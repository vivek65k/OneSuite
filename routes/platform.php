<?php

use App\Http\Controllers\Platform\ClientController;
use App\Http\Controllers\Platform\MailSetupController;
use App\Support\Mail\MailProviders;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/platform/clients');

Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
Route::get('clients/{client}/users', [ClientController::class, 'users'])->name('clients.users');
Route::post('clients/{client}/users/{user}/resend-invite', [ClientController::class, 'resendInvite'])->name('clients.resend-invite');
Route::post('clients/{client}/toggle-active', [ClientController::class, 'toggleActive'])->name('clients.toggle-active');

Route::get('mail-setup', [MailSetupController::class, 'edit'])->name('mail-setup.edit');
Route::put('mail-setup/{provider}', [MailSetupController::class, 'update'])->whereIn('provider', MailProviders::keys())->name('mail-setup.update');
