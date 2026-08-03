<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Habilitado extends BaseModel
{
    protected $table = 'habilitado';
    protected $primaryKey = 'id_habilitado';

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
        'habilitado'       => 'boolean',
    ];

    // ============ RELACIONES ============ //

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class, 'id_gestion');
    }

    public function mes(): BelongsTo
    {
        return $this->belongsTo(Mes::class, 'id_mes');
    }

    public function pago(): HasOne
    {
        // ofMany en vez de hasOne simple: normalmente hay un solo pago por
        // habilitado, pero si alguna vez llega a haber más de uno (ver caso
        // histórico de pago duplicado sin bloqueo de botón), esto garantiza
        // que siempre se resuelva al pago VIGENTE (pago=1) antes que a uno
        // anulado, y entre iguales al más reciente — sin esto, un hasOne
        // simple puede devolver cualquiera de los dos de forma arbitraria.
        return $this->hasOne(Pago::class, 'id_habilitado', 'id_habilitado')
            ->ofMany(['pago' => 'max', 'id_pago' => 'max']);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id');
    }

    // ============ SCOPES ============ //

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('habilitado', 1);
    }

    public function scopeDesdeFechaRegistro(Builder $query, Carbon $fecha): Builder
    {
        $año = $fecha->year;
        $mes = $fecha->month;

        return $query->where(function (Builder $q) use ($año, $mes): void {
            $q->whereHas('gestion', function (Builder $gq) use ($año): void {
                $gq->where('gestion', '>', $año);
            })->orWhere(function (Builder $q2) use ($año, $mes): void {
                $q2->whereHas('gestion', function (Builder $gq) use ($año): void {
                    $gq->where('gestion', '=', $año);
                })->whereHas('mes', function (Builder $mq) use ($mes): void {
                    $mq->where('mes', '>=', $mes);
                });
            });
        });
    }

    public function scopeOrdenadoPorGestionYMes(Builder $query): Builder
    {
        return $query->orderByDesc('id_gestion')
            ->orderByDesc('id_mes');
    }

    public function scopeConPago(Builder $query): Builder
    {
        return $query->has('pago');
    }

    public function scopeDelMes(Builder $query, string $gestion, Carbon $mes): Builder
    {
        return $query->whereHas('gestion', function (Builder $q) use ($gestion): void {
            $q->where('gestion', $gestion);
        })->whereHas('mes', function (Builder $q) use ($mes): void {
            $q->where('mes', $mes);
        });
    }
}