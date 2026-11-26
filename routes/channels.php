<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('olt-activity', function ($user) {
    return $user->hasPermission('activity.olt.view');
});

Broadcast::channel('mikrotik.activity', function ($user) {
    return $user->hasPermission('activity.mikrotik.view');
});
Broadcast::channel('location-monitoring', function ($user) {
    return $user->hasPermission('location.view');
});


