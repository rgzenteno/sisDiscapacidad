<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HistorialEstados extends Model
{
    use HasFactory;

    protected $table = 'historial_estados';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_persona',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'fecha_registro',
        'usuario_modificacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Relación con el modelo Persona
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Scope para obtener estados activos (fecha_fin es NULL)
     */
    public function scopeActivos($query)
    {
        return $query->whereNull('fecha_fin');
    }

    /**
     * Scope para obtener estados vigentes en una fecha específica
     */
    public function scopeVigentesEn($query, $fecha)
    {
        return $query->where('fecha_inicio', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $fecha);
            });
    }

    /**
     * Scope para obtener historial de una persona específica
     */
    public function scopePorPersona($query, $idPersona)
    {
        return $query->where('id_persona', $idPersona);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para obtener registros ordenados por fecha
     */
    public function scopeOrdenadoPorFecha($query, $direccion = 'desc')
    {
        return $query->orderBy('fecha_inicio', $direccion);
    }

    /**
     * Verificar si el estado está actualmente vigente
     */
    public function esVigente()
    {
        return is_null($this->fecha_fin) || $this->fecha_fin >= now()->toDateString();
    }

    /**
     * Verificar si el estado es histórico (ya terminó)
     */
    public function esHistorico()
    {
        return !is_null($this->fecha_fin) && $this->fecha_fin < now()->toDateString();
    }

    /**
     * Calcular duración del estado en días
     */
    public function getDuracionEnDias()
    {
        $fechaFin = $this->fecha_fin ? Carbon::parse($this->fecha_fin) : now();
        $fechaInicio = Carbon::parse($this->fecha_inicio);

        return $fechaInicio->diffInDays($fechaFin) + 1;
    }

    /**
     * Obtener el estado actual de una persona
     */
    public static function estadoActualPorPersona($idPersona)
    {
        return static::where('id_persona', $idPersona)
            ->activos()
            ->orderBy('fecha_inicio', 'desc')
            ->first();
    }

    /**
     * Obtener historial completo de una persona ordenado por fecha
     */
    public static function historialPorPersona($idPersona)
    {
        return static::where('id_persona', $idPersona)
            ->ordenadoPorFecha('desc')
            ->get();
    }

    /**
     * Mutator para limpiar el estado (solo trim, mantener el formato original)
     */
    public function setEstadoAttribute($value)
    {
        $this->attributes['estado'] = trim($value);
    }

    /**
     * Accessor para mostrar estado en formato legible
     */
    public function getEstadoFormateadoAttribute()
    {
        return ucwords(strtolower(str_replace('_', ' ', $this->estado)));
    }
}
