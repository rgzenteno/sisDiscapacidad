<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Agrega este "use"
use Illuminate\Database\Eloquent\Model;

class Carnet extends Model
{
    use HasFactory;
    // Nombre de la tabla
    protected $table = 'carnet';

    // Clave primaria personalizada
    protected $primaryKey = 'id_carnet';

    // Campos asignables masivamente
    protected $fillable = [
        'doc',
        'discapacidad',
        'fecha_emision',
        'fecha_vencimiento',
        'id_persona',
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set('America/La_Paz');
        $this->attributes["created_at"] = Carbon::now();
    }

    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set('America/La_Paz');
        $this->attributes["updated_at"] = Carbon::now();
    }
    // Habilitar timestamps
    public $timestamps = true;

    // Especificar si la clave primaria es auto-incremental
    public $incrementing = true;

    // Tipo de dato de la clave primaria
    protected $keyType = 'int';

    // Relación con la tabla 'persona'
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }
}