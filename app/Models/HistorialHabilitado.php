<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class HistorialHabilitado extends Model
{
    // Nombre de la tabla asociada
    protected $table = 'historial_habilitado';

    // Clave primaria personalizada
    protected $primaryKey = 'id_historial';

    // Campos asignables masivamente
    protected $fillable = [
        'habilitado_historial',
        'observacion_historial',
        'id',
        'id_persona',
        'id_gestion',
        'id_habilitado',
        'id_mes',
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

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    public function gestion()
    {
        return $this->belongsTo(Gestion::class, 'id_gestion', 'id_gestion');
    }

    public function habilitado()
    {
        return $this->belongsTo(Habilitado::class, 'id_habilitado', 'id_habilitado');
    }
}
