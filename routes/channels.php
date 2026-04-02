<?php

use Illuminate\Support\Facades\Broadcast;

// Private channel: user notifications
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Private channel: conversation messages
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()
        ->where('conversation_id', $conversationId)
        ->exists();
});

// Presence channel: team presence
Broadcast::channel('team.{teamId}', function ($user, $teamId) {
    if ((int) $user->team_id === (int) $teamId) {
        return ['id' => $user->id, 'name' => $user->name, 'status' => $user->presence_status];
    }
    return false;
});

// Private channel: team dashboard
Broadcast::channel('team.{teamId}.dashboard', function ($user, $teamId) {
    return (int) $user->team_id === (int) $teamId;
});
