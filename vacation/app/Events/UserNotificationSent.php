<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $senderName;
    public $recipientId;

    /**
     * @param int $recipientId - هوية المستخدم الذي سيستقبل الإشعار
     */
    public function __construct(string $message, string $senderName, int $recipientId)
    {
        $this->message = $message;
        $this->senderName = $senderName;
        $this->recipientId = $recipientId;
    }

    /**
     * القناة التي سيتم البث عليها.
     * يجب أن تكون القناة مرتبطة بهوية المستخدم المستقبل.
     */
    public function broadcastOn(): Channel
    {
        // اسم القناة: notifications.100 للمستخدم الذي هويته 100
        return new PrivateChannel('notifications.' . $this->recipientId);
    }

    /**
     * البيانات التي سيتم إرسالها إلى العميل
     */
    public function broadcastWith(): array
    {
        return [
            'body' => $this->message,
            'from' => $this->senderName,
            'time' => now()->toDateTimeString(),
        ];
    }
}