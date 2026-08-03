<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;

class Persona extends BaseModel
{
    use HasFactory, HasUuids;

    protected $table = 'persona';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id_persona';

    protected $fillable = [
        'id_persona',
        'nombre_persona',
        'apellido_persona',
        'nombre_completo',
        'distrito',
        'ci_persona',
        'complemento',
        'fecha_nacimiento',
        'observacion_persona',
        'tutor_nombre',
        'documento_respaldo',
        'tipo_registro',
        'fecha_registro',
        'id_tutor',
    ];

    protected $casts = [
        'fecha_registro'  => 'date:Y-m-d',
        'fecha_nacimiento'  => 'date:Y-m-d'
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    public function tutor()
    {
        return $this->belongsTo(Tutor::class, 'id_tutor', 'id_tutor');
    }

    public function carnet()
    {
        return $this->hasOne(Carnet::class, 'id_persona', 'id_persona');
    }

    public function habilitados()
    {
        return $this->hasMany(Habilitado::class, 'id_persona', 'id_persona');
    }

    public function habilitado()
    {
        return $this->belongsTo(Habilitado::class, 'id_habilitado', 'id_habilitado');
    }

    public function historialEstados()
    {
        return $this->hasMany(HistorialEstados::class, 'id_persona', 'id_persona');
    }

    public function ultimoEstado()
    {
        // El estado vigente es el que sigue abierto (fecha_fin nula) con la
        // fecha_inicio más reciente entre esos — no el de mayor id (con
        // estados intermedios insertados después, el más reciente en
        // insertarse ya no es necesariamente el más reciente en el tiempo) y
        // no cualquiera con fecha_fin nula sin más (si por datos
        // inconsistentes hay más de uno abierto a la vez, whereNull() solo
        // no garantiza cuál de esos trae). ofMany() agrega el criterio de
        // "el más reciente" entre los que siguen abiertos.
        return $this->hasOne(HistorialEstados::class, 'id_persona', 'id_persona')
            ->ofMany(['fecha_inicio' => 'max'], function ($query) {
                $query->whereNull('fecha_fin');
            });
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getNombreCompletoAttribute(?string $value)
    {
        if (!empty($value)) {
            return $value;
        }
        return trim("{$this->nombre_persona} {$this->apellido_persona}");
    }

    public function getEstadoActualAttribute()
    {
        return $this->ultimoEstado;
    }

    public function getCarnetVigenteAttribute()
    {
        return $this->carnet && $this->carnet->fecha_vencimiento >= now();
    }

    // ============================================================
    // SCOPES — TIPO DE REGISTRO
    // ============================================================

    public function scopePostulantes(Builder $query): Builder
    {
        return $query->where('tipo_registro', 'postulante');
    }

    public function scopeBeneficiarios(Builder $query): Builder
    {
        return $query->whereIn('tipo_registro', ['beneficiario', 'pendiente']);
    }

    // ============================================================
    // SCOPES — ESTADO (basados en ultimoEstado)
    // ============================================================

    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereHas('ultimoEstado', fn($q) => $q->whereIn('estado', ['activo', 'pagos_suspendidos']));
    }

    public function scopeBajaTemporal(Builder $query): Builder
    {
        return $query->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'baja_temporal'));
    }

    public function scopeBajaDefinitiva(Builder $query): Builder
    {
        return $query->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'baja_definitiva'));
    }

    public function scopeDepurado(Builder $query): Builder
    {
        return $query->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'depurado'));
    }

    // ============================================================
    // SCOPES — CARNET
    // ============================================================

    public function scopeSinCarnet(Builder $query): Builder
    {
        return $query->whereDoesntHave('carnet');
    }

    // ============================================================
    // SCOPES — BÚSQUEDA
    // ============================================================

    public function scopeSearch(Builder $query, ?string $buscador, $campos = []): Builder
    {
        if (!$buscador || empty($campos)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($buscador, $campos) {
            foreach ($campos as $campo) {
                $q->orWhere($campo, 'like', "%{$buscador}%");
            }
        });
    }

    public function scopeBusquedaGlobal(Builder $query, $search): Builder
    {
        if (!$search) {
            return $query;
        }

        $lowerSearch = strtolower($search);
        $searchUnderscore = str_replace(' ', '_', $lowerSearch);

        return $query->where(function (Builder $q) use ($search, $lowerSearch, $searchUnderscore) {
            $q->where('ci_persona', 'like', "%{$search}%")
                ->orWhere('complemento', 'like', "%{$search}%")
                ->orWhere('nombre_persona', 'like', "%{$search}%")
                ->orWhere('apellido_persona', 'like', "%{$search}%")
                ->orWhere('nombre_completo', 'like', "%{$search}%")
                ->orWhere('distrito', 'like', "%{$search}%")
                ->orWhere('fecha_registro', 'like', "%{$search}%")
                ->orWhere('observacion_persona', 'like', "%{$search}%")
                ->orWhere('tipo_registro', 'like', "%{$search}%")
                ->orWhereHas(
                    'tutor',
                    fn($q) =>
                    $q->where('nombre_tutor', 'like', "%{$search}%")
                        ->orWhere('apellido_tutor', 'like', "%{$search}%")
                        ->orWhere('ci_tutor', 'like', "%{$search}%")
                )
                ->orWhereHas('ultimoEstado', function (Builder $q) use ($search, $lowerSearch, $searchUnderscore) {
                    $q->where('estado', 'like', "%{$search}%");

                    if ($searchUnderscore !== $lowerSearch) {
                        $q->orWhere('estado', 'like', "%{$searchUnderscore}%");
                    }

                    if (!str_contains($lowerSearch, 'activo')) {
                        if (str_contains($lowerSearch, 'definitiva')) {
                            $q->orWhere('estado', 'baja_definitiva');
                        } elseif (str_contains($lowerSearch, 'temporal')) {
                            $q->orWhere('estado', 'baja_temporal');
                        } elseif (str_contains($lowerSearch, 'depurado')) {
                            $q->orWhere('estado', 'depurado');
                        } elseif (
                            str_contains($lowerSearch, 'pagos_suspendidos') ||
                            str_contains($lowerSearch, 'pagos') ||
                            str_contains($lowerSearch, 'suspendidos') ||
                            str_contains($lowerSearch, 'suspendido')
                        ) {
                            $q->orWhere('estado', 'pagos_suspendidos');
                        } elseif (str_contains($lowerSearch, 'baja')) {
                            $q->orWhereIn('estado', ['baja_temporal', 'baja_definitiva']);
                        }
                    }
                });
        });
    }

    // ============================================================
    // SCOPES — EAGER LOADING
    // ============================================================

    public function scopeConRelacionesCompletas(Builder $query): Builder
    {
        return $query->with([
            'tutor:id_tutor,nombre_tutor,apellido_tutor,ci_tutor,complemento_tutor,telefono,email,direccion,created_at',
            'carnet:id_carnet,id_persona,doc,discapacidad,fecha_emision,fecha_vencimiento',
            'ultimoEstado:historial_estados.id,historial_estados.id_persona,historial_estados.estado,historial_estados.fecha_inicio,historial_estados.fecha_fin,historial_estados.fecha_registro,historial_estados.usuario_modificacion,historial_estados.observaciones,historial_estados.created_at,historial_estados.updated_at',
        ]);
    }

    public function scopeOrdenar(Builder $query, ?string $campo, $direccion = 'asc'): Builder
    {
        return $query->orderBy($campo, $direccion);
    }
}