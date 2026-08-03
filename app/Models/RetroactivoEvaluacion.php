<?php

namespace App\Models;

class RetroactivoEvaluacion extends BaseModel
{
    protected $table = 'retroactivo_evaluacion';

    protected $fillable = [
        'id_persona',
        'id_gestion',
        'mes_original',
        'estado_baja',
        'motivo',
        'es_correccion_manual',
        'usuario_correccion',
        'fecha_evaluacion',
    ];

    protected $casts = [
        'es_correccion_manual' => 'boolean',
        'fecha_evaluacion' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function gestion()
    {
        return $this->belongsTo(Gestion::class, 'id_gestion');
    }
}
