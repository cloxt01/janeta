<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('olt-activity', function ($user) {
    return $user->isAdmin();
});



Broadcast::channel('mikrotik.activity', function ($user) {
    return $user->isAdmin();
});
Broadcast::channel('location-monitoring', function ($user) {
    return $user->hasPermission('location.view');
});


