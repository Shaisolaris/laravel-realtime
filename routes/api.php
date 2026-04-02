<?php

use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\PresenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Messaging
    Route::get('/conversations', [MessageController::class, 'conversations']);
    Route::get('/conversations/{id}/messages', [MessageController::class, 'messages']);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'send']);
    Route::post('/conversations/{id}/typing', [MessageController::class, 'typing']);
    Route::post('/conversations/{id}/read', [MessageController::class, 'markRead']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/push', [NotificationController::class, 'push']);

    // Activity Feed
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/activities', [ActivityController::class, 'store']);

    // Presence
    Route::post('/presence', [PresenceController::class, 'update']);
    Route::get('/presence/team', [PresenceController::class, 'team']);

    // Webhooks
    Route::get('/webhooks', [WebhookController::class, 'index']);
    Route::post('/webhooks', [WebhookController::class, 'store']);
    Route::get('/webhooks/{id}/deliveries', [WebhookController::class, 'deliveries']);
    Route::post('/webhooks/{id}/toggle', [WebhookController::class, 'toggle']);
    Route::post('/webhooks/{id}/test', [WebhookController::class, 'test']);
    Route::delete('/webhooks/{id}', [WebhookController::class, 'destroy']);
});
