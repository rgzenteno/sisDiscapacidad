<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'hierarchy_level',
    ];

    protected $casts = [
        'hierarchy_level' => 'integer',
    ];

    const MAX_HIERARCHY_LEVEL = 10;
    const SUPER_USUARIO_LEVEL = 10;

    /**
     * Verifica si este rol puede ser gestionado por un usuario
     */
    public function canBeEditedBy($user): bool
    {
        if ($user->hasRole('superUsuario')) {
            return true;
        }

        return $user->getMaxHierarchyLevel() > $this->hierarchy_level;
    }

    /**
     * Scope para ordenar por jerarquía
     */
    public function scopeByHierarchy($query)
    {
        return $query->orderBy('hierarchy_level', 'desc');
    }

    /**
     * Verifica si es el nivel máximo
     */
    public function isMaxHierarchy(): bool
    {
        return $this->hierarchy_level === self::MAX_HIERARCHY_LEVEL;
    }
}
