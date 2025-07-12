<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    use HasFactory;
    
    protected $fillable = [
        //
    ];
    
    // Define a one-to-many relationship between leaves and leave_types
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    // Define a one-to-many relationship between leaves and leave_categories
    public function leaveCategory(): BelongsTo
    {
        return $this->belongsTo(LeaveCategory::class);
    }

    // Define a one-to-many relationship between leaves and users
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Define a one-to-many relationship between leaves and statuses
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

}
