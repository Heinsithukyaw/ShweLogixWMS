<?php

namespace App\Services;

use App\Events\BaseEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;

class BroadcastService
{
    /**
     * Broadcast an event to the frontend.
     *
     * @param  BaseEvent  $event
     * @param  string  $channel
     * @return void
     */
    public static function broadcastEvent(BaseEvent $event, string $channel = 'events')
    {
        try {
            // Broadcast the event to the specified channel
            Broadcast::channel($channel, function ($user) {
                return true;
            });
            
            // Log the broadcast
            Log::info('Event broadcasted', [
                'event' => $event->getName(),
                'channel' => $channel,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast event', [
                'event' => $event->getName(),
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Broadcast a notification to specific users.
     *
     * @param  string  $type
     * @param  string  $message
     * @param  array  $data
     * @param  array  $userIds
     * @return void
     */
    public static function broadcastNotification(string $type, string $message, array $data = [], array $userIds = [])
    {
        try {
            // If no user IDs are specified, broadcast to all users
            if (empty($userIds)) {
                Broadcast::channel('notifications', function ($user) {
                    return true;
                });
                
                // Broadcast to the general notifications channel
                event(new \App\Events\Notification\NotificationEvent($type, $message, $data, []));
            } else {
                // Broadcast to each user's private channel
                foreach ($userIds as $userId) {
                    Broadcast::channel('notifications.user.' . $userId, function ($user) use ($userId) {
                        return (int) $user->id === (int) $userId;
                    });
                    
                    // Broadcast to the user's private channel
                    event(new \App\Events\Notification\NotificationEvent($type, $message, $data, [$userId]));
                }
            }
            
            // Log the broadcast
            Log::info('Notification broadcasted', [
                'type' => $type,
                'users' => count($userIds),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast notification', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}