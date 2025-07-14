<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// General events channel
Broadcast::channel('events', function ($user) {
    return true; // Public channel, anyone can subscribe
});

// General notifications channel
Broadcast::channel('notifications', function ($user) {
    return true; // Public channel, anyone can subscribe
});

// User-specific notifications channel
Broadcast::channel('notifications.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Inventory events channel
Broadcast::channel('inventory', function ($user) {
    // Only users with inventory management permissions can subscribe
    return $user->hasPermission('inventory.view');
});

// Warehouse events channel
Broadcast::channel('warehouse', function ($user) {
    // Only users with warehouse management permissions can subscribe
    return $user->hasPermission('warehouse.view');
});

// Inbound events channel
Broadcast::channel('inbound', function ($user) {
    // Only users with inbound management permissions can subscribe
    return $user->hasPermission('inbound.view');
});
