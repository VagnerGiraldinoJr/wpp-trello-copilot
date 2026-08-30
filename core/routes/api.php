<?php

use App\Http\Controllers\Api\TrelloWebhookController;
use App\Http\Controllers\Api\WhatsAppPendingMessagesController;
use App\Http\Controllers\Api\WhatsAppSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('whatsapp')->group(function () {
    Route::post('/sync', [WhatsAppSyncController::class, 'store']);
    Route::get('/pending-messages', [WhatsAppPendingMessagesController::class, 'index']);
});

Route::prefix('trello')->group(function () {
    Route::post('/webhook', [TrelloWebhookController::class, 'store']);
    Route::get('/webhook', [TrelloWebhookController::class, 'verify']);
});
