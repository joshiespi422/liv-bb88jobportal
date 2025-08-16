<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // Define a one-to-many relationship between time_logs and users
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
