<?php

use App\Http\Controllers\Admin\MessagingController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard/messaging')->name('dashboard.messaging.')->middleware(['auth', 'role:admin,operator'])->group(function (): void {
    Route::get('/', [MessagingController::class, 'overview'])->name('index');
    Route::get('/contacts', [MessagingController::class, 'contacts'])->name('contacts');
    Route::post('/contacts', [MessagingController::class, 'saveContact'])->name('contacts.store');
    Route::post('/contacts/import', [MessagingController::class, 'importContacts'])->name('contacts.import');
    Route::patch('/contacts/{id}', [MessagingController::class, 'saveContact'])->whereUuid('id')->name('contacts.update');
    Route::get('/templates', [MessagingController::class, 'templates'])->name('templates');
    Route::post('/templates', [MessagingController::class, 'saveTemplate'])->name('templates.store');
    Route::patch('/templates/{id}', [MessagingController::class, 'saveTemplate'])->whereUuid('id')->name('templates.update');
    Route::get('/campaigns', [MessagingController::class, 'campaigns'])->name('campaigns');
    Route::get('/campaigns/create', [MessagingController::class, 'compose'])->name('campaigns.create');
    Route::post('/campaigns/preview', [MessagingController::class, 'preview'])->name('campaigns.preview');
    Route::post('/campaigns', [MessagingController::class, 'createCampaign'])->name('campaigns.store');
    Route::get('/campaigns/reconcile/{key}', [MessagingController::class, 'reconcile'])->whereUuid('key')->name('campaigns.reconcile');
    Route::get('/campaigns/{id}', [MessagingController::class, 'campaignDetail'])->whereUuid('id')->name('campaigns.show');
    Route::post('/campaigns/{id}/{action}', [MessagingController::class, 'campaignAction'])->whereUuid('id')->whereIn('action', ['start', 'pause', 'resume', 'cancel'])->name('campaigns.action');
    Route::get('/history', [MessagingController::class, 'history'])->name('history');
    Route::post('/messages/{id}/retry', [MessagingController::class, 'retry'])->whereUuid('id')->name('messages.retry');
    Route::get('/recipients', [MessagingController::class, 'recipients'])->name('recipients');
    Route::middleware('role:admin')->group(function (): void {
        Route::get('/connection', [MessagingController::class, 'connection'])->name('connection');
        Route::post('/connection/{action}', [MessagingController::class, 'connectionAction'])->whereIn('action', ['connect', 'disconnect', 'replace-account'])->name('connection.action');
    });
});
