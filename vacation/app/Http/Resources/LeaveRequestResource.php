<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'leave_type'  => $this->leave_type,
            'start_date'  => $this->start_date,
            'end_date'    => $this->end_date,
            'days'        => $this->days,
            'status'      => $this->status,
            'notes'       => $this->notes,
   
            'admin_notes' => $this->admin_notes, // تأكد أنه موجود في الداتابيز
            

            'created_at'  => $this->created_at ? $this->created_at->toDateTimeString() : null,
            
            // 👇 إرجاع أوبجكت اليوزر فقط إذا تم تحميله (Eager Loading)
            'user'        => new UserResource($this->whenLoaded('user')),
            'branch'    => new BranchResource($this->whenLoaded('branch')),
        ];
    }
}
