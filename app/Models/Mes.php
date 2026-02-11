<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mes extends Model
{
    // Nombre de la tabla asociada
    protected $table = 'mes';
    // Clave primaria personalizada
    protected $primaryKey = 'id_mes';

    // Campos asignables masivamente
    protected $fillable = [
        'mes',
        'monto',
        'presupuesto',
        'id_gestion'
    ];

    // ============ RELACIONES ============//
    public function gestion()
    {
        return $this->belongsTo(Gestion::class, 'id_gestion');
    }

    public function habilitados()
    {
        return $this->hasMany(Habilitado::class ,'id_mes');
    }

}
