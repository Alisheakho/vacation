<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // $this هنا يشير للمصفوفة التي مررناها من الكونترولر
        return [
            'token' => $this['token'],
            
            // نأخذ الرول إما من المصفوفة الممررة أو من اليوزر نفسه
            'role'  => $this['role'] ?? $this['user']->type, 
            
            // نستدعي ريسورس اليوزر لضمان نظافة البيانات
            'user'  => new UserResource($this['user']),
        ];
    }
}