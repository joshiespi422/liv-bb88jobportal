<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Leave extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'leave_category_id',
        'status_id',
        'request_date',
        'reason',
        'proof',
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

    // Define a one-to-many polymorphic relationship between leaves and notifications
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
