<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Foreman-specific notification channel — tenant-scoped
Broadcast::channel('foreman.{foremanId}', function ($user, $foremanId) {
    return $user->foreman && $user->foreman->id === (int) $foremanId;
});

// Project-wide broadcast for timelines/budget updates
Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    return $user->can('view-project', $projectId);
});
