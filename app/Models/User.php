<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'cargo',
        'digital_signature',
        'estado',
        'habilitado',
        'email',
        'password',
        'password_reset_required',
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set('America/La_Paz');
        $this->attributes["created_at"] = Carbon::now();
    }

    public function getMaxHierarchyLevel(): int
    {
        return $this->roles()->max('hierarchy_level') ?? 0;
    }

    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set('America/La_Paz');
        $this->attributes["updated_at"] = Carbon::now();
    }

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
            'password_reset_required' => 'boolean',
            'habilitado' => 'boolean',
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }
}
