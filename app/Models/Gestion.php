<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gestion extends Model
{
    // Nombre de la tabla asociada
    protected $table = 'gestion';
    // Clave primaria personalizada
    protected $primaryKey = 'id_gestion';

    // Campos asignables masivamente
    protected $fillable = [
        'gestion',
        'presupuesto_anual',
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
}
