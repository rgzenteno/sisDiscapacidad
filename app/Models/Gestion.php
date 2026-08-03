<?php

namespace App\Models;

class Gestion extends BaseModel
{
    protected $table = 'gestion';
    protected $primaryKey = 'id_gestion';

    protected $fillable = [
        'gestion',
        'presupuesto_anual',
        'retroactivos_habilitado',
        'caja_cerrada',
        'fecha_cierre_caja',
    ];

    protected $casts = [
        'retroactivos_habilitado' => 'boolean',
        'caja_cerrada' => 'boolean',
        'fecha_cierre_caja' => 'datetime',
    ];

    // ============ RELACIONES ============//
    public function meses()
    {
        return $this->hasMany(Mes::class, 'id_gestion');
    }

    public function habilitados()
    {
        return $this->hasMany(Habilitado::class, 'id_gestion');
    }

    // ============ CIERRE DE CAJA ============ //

    public function estaCerrada(): bool
    {
        return (bool) $this->caja_cerrada;
    }
}
