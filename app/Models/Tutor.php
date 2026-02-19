<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{

    use HasFactory;
    // Nombre de la tabla asociada
    protected $table = 'tutor';

    // Clave primaria personalizada
    protected $primaryKey = 'id_tutor';

    // Campos asignables masivamente
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

    protected $appends = ['nombre_completo'];

    // ============ RELACIONES ============//

    // Relación con

    // ============ SCOPES ============ //

    // Scope: nombreTutor
    public function getNombreCompletoAttribute()
    {
        return trim("{$this->apellido_tutor} {$this->nombre_tutor}");
    }
}
