<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('users.{id}.notifications', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('users.{id}.maintenance', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ops.chat', function ($user) {
    return $user !== null && $user->hasApprovedStatus();
});

Broadcast::channel('dashboard.telemetry', function ($user) {
    return $user !== null && $user->hasApprovedStatus();
});

Broadcast::channel('servers.overview', function ($user) {
    return $user !== null && $user->hasApprovedStatus();
});

Broadcast::channel('servers.{serverId}', function ($user, $serverId) {
    return $user !== null && $user->hasApprovedStatus();
});

Broadcast::channel('ops.admin', function ($user) {
    return $user !== null && $user->isDepartmentHead();
});
