<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoSuspendido extends BaseModel
{
    protected $table = 'pago_suspendido';

    protected $fillable = [
        'id_persona',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
        'usuario_modificacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin'    => 'date:Y-m-d',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    // ============ SCOPES ============ //

    public function scopeAbierta(Builder $query): Builder
    {
        return $query->whereNull('fecha_fin');
    }

    public function scopeVigenteEn(Builder $query, Carbon $fecha): Builder
    {
        return $query->where('fecha_inicio', '<=', $fecha)
            ->where(function (Builder $q) use ($fecha): void {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $fecha);
            });
    }
}
