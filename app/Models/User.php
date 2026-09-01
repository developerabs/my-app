<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\landlord\Reseller;
use App\Models\landlord\Tenant;
use App\Traits\HasTrash;
use App\Traits\LogIP;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, SoftDeletes, HasTrash, Notifiable, HasRoles, LogsActivity, LogIP;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'phone',
        'role_id',
        'remember_token',
        'company_name',
        'reseller_id',
        'password',
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

    protected static function booted()
    {
        parent::booted();

        // শুধুমাত্র UI লিস্ট ক্যাশ ক্লিয়ার করার জন্য ক্লোজার
        $clearUICache = function ($model) {
            Cache::tags([tenant_tag()])->forget('all_users_' . tenant('id'));
        };

        static::saved($clearUICache);
        static::deleted($clearUICache);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function userBranches()
    {
        return $this->hasMany(UserBranch::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'user_branches', 'user_id', 'branch_id')
            ->using(UserBranch::class)
            ->withPivot(['id', 'is_default'])
            ->withTimestamps();
    }
}
