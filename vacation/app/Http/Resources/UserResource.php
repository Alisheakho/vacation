<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
        'id'    => $this->id,
        'name'  => $this->name,
      'employee_id' => $this->employee_id,
        'section' => $this->section,
        'jobe_title' => $this->jobe_title,
         'branch' => new BranchResource($this->whenLoaded('branch')),
    
        // أي بيانات أخرى خاصة باليوزر فقط
    ];
    }
}
