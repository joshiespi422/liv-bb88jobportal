<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

     /**
     * Get the user type that owns the user.
     */
    public function userType(): BelongsTo 
    {
        return $this->belongsTo(UserType::class);
    }

    /**
     * Defines a one-to-one relationship with users and user_employees
     */
    public function employeeDetails()
    {
        return $this->hasOne(UserEmployee::class);
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $typeName
     * @return bool
     */
    public function hasRole(string $typeName): bool 
    {
        return $this->userType && $this->userType->type_name === $typeName;
    }
}
