<?php

namespace App\Events;

use App\Models\LeaveRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewLeaveRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $leaveRequest;
    public $targetUserId; // غيرنا الاسم ليكون عام (ممكن مدير وممكن موظف)
    public $eventType;    // نوع الحدث: 'created' أو 'updated'

    // بنمرر الطلب + الآيدي المستهدف + نوع الحدث (افتراضياً created)
    public function __construct(LeaveRequest $leaveRequest, $targetUserId, $eventType = 'created')
    {
        $this->leaveRequest = $leaveRequest;
        $this->targetUserId = $targetUserId;
        $this->eventType = $eventType;
    }

    public function broadcastOn(): array
    {
        // نرسل للقناة بناءً على الآيدي المستهدف (سواء مدير أو موظف)
        return [
            new PrivateChannel('chat.' . $this->targetUserId),
        ];
    }

    public function broadcastAs(): string
    {
        // 👇 هنا الذكاء: بنغير الاسم حسب النوع
        return $this->eventType === 'created' ? 'leave.created' : 'leave.updated';
    }

    public function broadcastWith()
    {
        // تحديد الرسالة حسب الحالة
        $msg = $this->eventType === 'created' 
            ? "طلب إجازة جديد ({$this->leaveRequest->leave_type})"
            : "تم تحديث حالة طلبك إلى: {$this->leaveRequest->status}";

        return [
            'id'         => $this->leaveRequest->id,
            'status'     => $this->leaveRequest->status,
            'message'    => $msg,
            // رجعنا المودل كامل عشان التحديث
            'leaveRequest' => $this->leaveRequest, 
            'type'       => $this->eventType, // مفيد لفلتر يعرف شو صار
        ];
    }
}