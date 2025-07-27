<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'qr_code',
        'position',
        'picture',
        'address',
        'gender',
        'bday'
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
    public function employeeDetails(): HasOne
    {
        return $this->hasOne(UserEmployee::class);
    }

    /**
     * Defines a one-to-one relationship with users and user_interns
     */
    public function internDetails(): HasOne
    {
        return $this->hasOne(UserIntern::class);
    }

    /**
     * Defines a many-to-many relationship with users and tasks (pivot table)
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    /**
     * Defines a one-to-many relationship with users and accomplishments
     */
    public function accomplishments(): HasMany
    {
        return $this->hasMany(Accomplishment::class);
    }

    /**
     * Define a one-to-many relationship between users and leaves
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Define a one-to-many relationship between users and project_issues
     */
    public function projectIssues(): HasMany
    {
        return $this->hasMany(ProjectIssue::class);
    }

    /**
     * Define a one-to-many relationship between users and comments
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Define a one-to-many relationship between users and notifications
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
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
