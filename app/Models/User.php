<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

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

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_reset_required' => 'boolean',
            'habilitado' => 'boolean',
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function getMaxHierarchyLevel(): int
    {
        return $this->roles()->max('hierarchy_level') ?? 0;
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
}
