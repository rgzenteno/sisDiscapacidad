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
        'fecha_registro' => 'date:Y-m-d',
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
        return $this->hasOne(HistorialEstados::class, 'id_persona', 'id_persona')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('historial_estados')
                    ->groupBy('id_persona');
            });
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

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
        return $this->carnet && $this->carnet->fecha_vencimiento >= now();
    }

    // ============================================================
    // SCOPES — TIPO DE REGISTRO
    // ============================================================

    public function scopePostulantes($query)
    {
        return $query->where('tipo_registro', 'postulante');
    }

    public function scopeBeneficiarios($query)
    {
        return $query->whereIn('tipo_registro', ['beneficiario', 'pendiente']);
    }

    // ============================================================
    // SCOPES — ESTADO (basados en ultimoEstado)
    // ============================================================

    public function scopeActivos($query)
    {
        return $query->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'activo'));
    }

    public function scopeBajaTemporal($query)
    {
        return $query->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'baja_temporal'));
    }

    public function scopeBajaDefinitiva($query)
    {
        return $query->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'baja_definitiva'));
    }

    public function scopeDepurado($query)
    {
        return $query->whereHas('ultimoEstado', fn($q) => $q->where('estado', 'depurado'));
    }

    // ============================================================
    // SCOPES — CARNET
    // ============================================================

    public function scopeSinCarnet($query)
    {
        return $query->whereDoesntHave('carnet');
    }

    // ============================================================
    // SCOPES — BÚSQUEDA
    // ============================================================

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
        $searchUnderscore = str_replace(' ', '_', $lowerSearch);

        return $query->where(function ($q) use ($search, $lowerSearch, $searchUnderscore) {
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
                ->orWhereHas('ultimoEstado', function ($q) use ($search, $lowerSearch, $searchUnderscore) {
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

    public function scopeConRelacionesCompletas($query)
    {
        return $query->with([
            'tutor:id_tutor,nombre_tutor,apellido_tutor,ci_tutor,complemento_tutor,telefono,email,direccion',
            'carnet:id_carnet,id_persona,doc,discapacidad,fecha_emision,fecha_vencimiento',
            'ultimoEstado:id,id_persona,estado,fecha_inicio,fecha_fin,fecha_registro,usuario_modificacion,observaciones,created_at,updated_at',
        ]);
    }

    public function scopeOrdenar($query, $campo, $direccion = 'asc')
    {
        return $query->orderBy($campo, $direccion);
    }
}