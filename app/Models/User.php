<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Backpack\CRUD\app\Models\Traits\CrudTrait; // <------------------------------- this one
use Laravel\Passport\HasApiTokens as PassportHasApiTokens;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;//

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use CrudTrait;
    use HasRoles;
    use PassportHasApiTokens;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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


    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'user_branch', 'user_id', 'branch_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

}
