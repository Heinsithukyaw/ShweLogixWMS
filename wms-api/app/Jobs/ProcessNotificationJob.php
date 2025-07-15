<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use App\Services\BroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The notification type.
     *
     * @var string
     */
    protected $type;

    /**
     * The notification message.
     *
     * @var string
     */
    protected $message;

    /**
     * The notification data.
     *
     * @var array
     */
    protected $data;

    /**
     * The notification recipients.
     *
     * @var array
     */
    protected $recipients;

    /**
     * Create a new job instance.
     *
     * @param  string  $type
     * @param  string  $message
     * @param  array  $data
     * @param  array  $recipients
     * @return void
     */
    public function __construct(string $type, string $message, array $data = [], array $recipients = [])
    {
        $this->type = $type;
        $this->message = $message;
        $this->data = $data;
        $this->recipients = $recipients;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Store the notification in the database
            $this->storeNotifications();
            
            // Broadcast the notification to the frontend
            BroadcastService::broadcastNotification(
                $this->type,
                $this->message,
                $this->data,
                $this->recipients
            );
            
            Log::info('Notification processed successfully', [
                'type' => $this->type,
                'recipients' => count($this->recipients),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process notification', [
                'type' => $this->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Retry the job with exponential backoff
            $this->release(30);
        }
    }
    
    /**
     * Store the notifications in the database.
     *
     * @return void
     */
    protected function storeNotifications()
    {
        // If no recipients are specified, notify all users
        if (empty($this->recipients)) {
            $users = User::all();
            
            foreach ($users as $user) {
                $this->createNotification($user->id);
            }
        } else {
            // Create a notification for each recipient
            foreach ($this->recipients as $recipientId) {
                $this->createNotification($recipientId);
            }
        }
    }
    
    /**
     * Create a notification for a user.
     *
     * @param  int  $userId
     * @return Notification
     */
    protected function createNotification($userId)
    {
        return Notification::create([
            'type' => $this->type,
            'message' => $this->message,
            'data' => $this->data,
            'user_id' => $userId,
            'is_read' => false,
        ]);
    }
}