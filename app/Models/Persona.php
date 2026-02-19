<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
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
        'fecha_registro' => 'date'
    ];

    // ============ RELACIONES ============ //

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

    public function historialEstados()
    {
        return $this->hasMany(HistorialEstados::class, 'id_persona', 'id_persona');
    }

    // 👇 NUEVA RELACIÓN: Para obtener solo el último estado
    public function ultimoEstado()
    {
        return $this->hasOne(HistorialEstados::class, 'id_persona', 'id_persona')
            ->orderBy('fecha_registro', 'desc')
            ->orderBy('id', 'desc');
    }

    // ============ ACCESSORS ============ //

    public function getNombreCompletoAttribute($value)
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
        if (!$this->carnet) {
            return false;
        }
        return $this->carnet->fecha_vencimiento >= now();
    }

    // ============ SCOPES ============ //

    public function scopeSearch($query, $buscador, $campos = [])
    {
        if (!$buscador || empty($campos)) {
            return $query;
        }

        return $query->where(function ($q) use ($buscador, $campos) {
            foreach ($campos as $campo) {
                $q->orWhere($campo, 'like', "%{$buscador}%");
            }
        });
    }

    public function scopeBusquedaGlobal($query, $search)
    {
        if (!$search) {
            return $query;
        }

        $lowerSearch = strtolower($search);

        return $query->where(function ($q) use ($search, $lowerSearch) {
            $q->where('ci_persona', 'like', "%{$search}%")
                ->orWhere('complemento', 'like', "%{$search}%")
                ->orWhere('nombre_persona', 'like', "%{$search}%")
                ->orWhere('apellido_persona', 'like', "%{$search}%")
                ->orWhere('nombre_completo', 'like', "%{$search}%")
                ->orWhere('distrito', 'like', "%{$search}%")
                ->orWhere('observacion_persona', 'like', "%{$search}%")
                ->orWhere('tipo_registro', 'like', "%{$search}%") // ✅ CORREGIDO
                ->orWhereHas('tutor', function ($tutorQuery) use ($search) {
                    $tutorQuery->where('nombre_tutor', 'like', "%{$search}%")
                        ->orWhere('apellido_tutor', 'like', "%{$search}%")
                        ->orWhere('ci_tutor', 'like', "%{$search}%");
                })
                ->orWhereHas('ultimoEstado', function ($estadoQuery) use ($search, $lowerSearch) {
                    $estadoQuery->where(function ($stateQuery) use ($search, $lowerSearch) {
                        // Búsqueda principal
                        $stateQuery->where('estado', 'like', "%{$search}%");

                        // Solo agregar búsquedas especiales si no está buscando "activo"
                        if (!str_contains($lowerSearch, 'activo')) {
                            // Búsqueda de palabras completas
                            if (str_contains($lowerSearch, 'definitiva')) {
                                $stateQuery->orWhere('estado', 'like', '%baja_definitiva%');
                            }

                            if (str_contains($lowerSearch, 'temporal')) {
                                $stateQuery->orWhere('estado', 'like', '%baja_temporal%');
                            }

                            if (
                                str_contains($lowerSearch, 'baja') &&
                                !str_contains($lowerSearch, 'temporal') &&
                                !str_contains($lowerSearch, 'definitiva')
                            ) {
                                $stateQuery->orWhere('estado', 'like', '%baja_temporal%')
                                    ->orWhere('estado', 'like', '%baja_definitiva%');
                            }
                        }

                        // Búsqueda con guión bajo
                        $searchWithUnderscore = str_replace(' ', '_', $lowerSearch);
                        if ($searchWithUnderscore !== $lowerSearch) {
                            $stateQuery->orWhere('estado', 'like', "%{$searchWithUnderscore}%");
                        }
                    });
                });
        });
    }

    // 👇 CORREGIDO: Scope con relación correcta y tabla específica
    public function scopeConRelacionesCompletas($query)
    {
        return $query->with([
            'tutor:id_tutor,nombre_tutor,apellido_tutor,ci_tutor,complemento_tutor,telefono,email,direccion',
            'carnet:id_carnet,id_persona,doc,discapacidad,fecha_emision,fecha_vencimiento',
            'ultimoEstado' => function ($q) {
                $q->select([
                    'historial_estados.id',
                    'historial_estados.id_persona',
                    'historial_estados.estado',
                    'historial_estados.fecha_inicio',
                    'historial_estados.fecha_fin',
                    'historial_estados.fecha_registro',
                    'historial_estados.usuario_modificacion',
                    'historial_estados.observaciones',
                    'historial_estados.created_at',
                    'historial_estados.updated_at'
                ]);
            }
        ]);
    }

    public function scopeOrdenar($query, $campo, $direccion = 'asc')
    {
        return $query->orderBy($campo, $direccion);
    }

    public function scopeActivo($query)
    {
        return $query->whereDoesntHave('ultimoEstado', function ($q) {
            $q->where('estado', 'baja_definitiva');
        });
    }

    public function scopeSinCarnet($query)
    {
        return $query->whereDoesntHave('carnet');
    }

    public function scopePostulantes($query)
    {
        return $query->where('tipo_registro', 'postulante');
    }

    public function scopeBeneficiarios($query)
    {
        return $query->whereIn('tipo_registro', ['beneficiario', 'pendiente']);
    }

    public function scopeConCarnetVigente($query)
    {
        return $query->whereHas('carnet', function ($q) {
            $q->where('fecha_vencimiento', '>=', now());
        });
    }

    public function scopeConCarnetVencido($query)
    {
        return $query->whereHas('carnet', function ($q) {
            $q->where('fecha_vencimiento', '<', now());
        });
    }
}
