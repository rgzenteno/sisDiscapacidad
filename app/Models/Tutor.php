<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Tutor extends BaseModel
{
    use HasFactory;

    protected $table = 'tutor';
    protected $primaryKey = 'id_tutor';

    protected $fillable = [
        'nombre_tutor',
        'apellido_tutor',
        'ci_tutor',
        'complemento_tutor',
        'fecha_nacimiento',
        'telefono',
        'email',
        'direccion'
    ];

    protected $casts = [
        'fecha_nacimiento'  => 'date:Y-m-d'
    ];

    protected $appends = ['nombre_completo'];

    // ============================================================
    // RELACIONES
    // ============================================================

    public function personas()
    {
        return $this->hasMany(Persona::class, 'id_tutor', 'id_tutor');
    }

    public function tutorados()
    {
        return $this->hasMany(Persona::class, 'id_tutor', 'id_tutor')
            ->beneficiarios();
    }

    public function tutoradosActivos()
    {
        return $this->hasMany(Persona::class, 'id_tutor', 'id_tutor')
            ->beneficiarios()
            ->activos();
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getNombreCompletoAttribute()
    {
        return trim("{$this->apellido_tutor} {$this->nombre_tutor}");
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Agrega conteo de tutorados (beneficiarios) al query.
     * Uso: Tutor::conConteoTutorados()->get()
     */
    public function scopeConConteoTutorados(Builder $query): Builder
    {
        return $query->withCount([
            'personas as total_tutorados' => fn($q) => $q->beneficiarios(),
            'personas as tutorados_activos' => fn($q) => $q->beneficiarios()->activos(),
        ]);
    }

    /**
     * Filtra tutores que tengan al menos un tutorado activo.
     */
    public function scopeConTutoradosActivos(Builder $query): Builder
    {
        return $query->whereHas('tutoradosActivos');
    }

    /**
     * Filtra tutores sin ningún tutorado asignado.
     */
    public function scopeSinTutorados(Builder $query): Builder
    {
        return $query->whereDoesntHave('tutorados');
    }
}
