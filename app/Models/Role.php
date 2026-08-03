<?php

namespace App\Models;

use DateTimeInterface;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Builder;

class Role extends SpatieRole implements RoleContract
{
    protected $fillable = [
        'name',
        'guard_name',
        'hierarchy_level',
    ];

    protected $casts = [
        'hierarchy_level' => 'integer',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-dd-m-Y H:i:s');
    }

    public const MAX_HIERARCHY_LEVEL = 10;
    public const SUPER_USUARIO_LEVEL = 10;

    public function canBeEditedBy(User $user): bool
    {
        if ($user->hasRole('superUsuario')) {
            return true;
        }

        return $user->getMaxHierarchyLevel() > $this->hierarchy_level;
    }

    public function scopeByHierarchy(Builder $query): Builder
    {
        return $query->orderBy('hierarchy_level', 'desc');
    }

    public function isMaxHierarchy(): bool
    {
        return $this->hierarchy_level === self::MAX_HIERARCHY_LEVEL;
    }
}