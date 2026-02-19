<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Habilitado extends Model
{
    // Nombre de la tabla asociada
    protected $table = 'habilitado';
    // Clave primaria personalizada
    protected $primaryKey = 'id_habilitado';

    // Campos asignables masivamente
    protected $fillable = [
        'habilitado',
        'observaciones_habilitado',
        'fecha_habilitado',
        'id',
        'id_persona',
        'id_gestion',
        'id_mes',
    ];

    protected $casts = [
        'habilitado' => 'boolean'
    ];

    // ============ RELACIONES ============//

    // Relación con Persona
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    // Relación con Gestion
    public function gestion()
    {
        return $this->belongsTo(Gestion::class, 'id_gestion');
    }

    // Relación con Mes
    public function mes()
    {
        return $this->belongsTo(Mes::class, 'id_mes');
    }

    // Relación con Pago
    public function pago()
    {
        return $this->hasOne(Pago::class, 'id_habilitado');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    // ============ SCOPES ============//

    // En el modelo Habilitado
public function scopeActivo($query)
{
    return $query->where('habilitado', 1); // o true
}

    public function scopeDesdeFechaRegistro($query, $fecha)
    {
        $año = $fecha->year;
        $mes = $fecha->month;

        return $query->where(function ($q) use ($año, $mes) {
            // Años posteriores (sin importar el mes)
            $q->whereHas('gestion', function ($gq) use ($año) {
                $gq->where('gestion', '>', $año);
            })
                // O mismo año pero mes mayor o igual
                ->orWhere(function ($q2) use ($año, $mes) {
                    $q2->whereHas('gestion', function ($gq) use ($año) {
                        $gq->where('gestion', '=', $año);
                    })->whereHas('mes', function ($mq) use ($mes) {
                        $mq->where('mes', '>=', $mes);
                    });
                });
        });
    }

    public function scopeOrdenadoPorGestionYMes($query)
    {
        return $query->orderByDesc('id_gestion')
            ->orderByDesc('id_mes');
    }

    public function scopeConPago($query)
    {
        return $query->has('pago');
    }

    public function scopeDelMes($query, $gestion, $mes)
    {
        return $query->whereHas('gestion', function ($q) use ($gestion) {
            $q->where('gestion', $gestion);
        })->whereHas('mes', function ($q) use ($mes) {
            $q->where('mes', $mes);
        });
    }
}
