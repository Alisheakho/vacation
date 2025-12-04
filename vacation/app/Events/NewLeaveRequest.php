<?php

namespace App\Events;

use App\Models\LeaveRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewLeaveRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $leaveRequest;
    public $managerId;

    // نستلم طلب الإجازة كاملاً + آيدي المدير الذي سيستلم الإشعار
    public function __construct(LeaveRequest $leaveRequest, $managerId)
    {
        $this->leaveRequest = $leaveRequest;
        $this->managerId = $managerId;
    }

    public function broadcastOn(): array
    {
        // نرسل لقناة المدير (مثلاً chat.1)
        return [
            new PrivateChannel('chat.' . $this->managerId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'leave.created';
    }

    // هنا نحدد البيانات التي ستظهر في الجافاسكربت (من جدولك بالضبط)
    public function broadcastWith()
    {
        return [
            'id' => $this->leaveRequest->id,
            'employee' => $this->leaveRequest->user->name, // اسم الموظف
            'type' => $this->leaveRequest->leave_type,     // نوع الإجازة
            'start_date' => $this->leaveRequest->start_date,
            'days' => $this->leaveRequest->days,
            'status' => $this->leaveRequest->status,
            'message' => "طلب إجازة جديد (" . $this->leaveRequest->leave_type . ")"
        ];
    }
}