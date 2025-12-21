<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'leave_type',
        'start_date',
        'end_date',
        'days',
        'status',
        'escalated',
        'notes',
    ];

    
/*     protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'escalated'  => 'boolean',
        'leave_type' => LeaveType::class,
        'status'     => LeaveStatus::class,
    ]; */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

  


    public function medicalFile(): HasOne
    {
        return $this->hasOne(MedicalFile::class);
    }
}
