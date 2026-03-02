<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
return [
            'id'      => $this->id,
            'name'    => $this->name,
           
            
            // 👇👇 هنا السحر: إرجاع المدير كـ Object كامل 👇👇
            // نستخدم whenLoaded عشان لو ما حملنا المدير ما يضرب الكود
            'manager' => new UserResource($this->whenLoaded('manager')),
        ];
    }
}
