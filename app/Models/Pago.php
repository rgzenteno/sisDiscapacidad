<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    // Nombre de la tabla asociada
    protected $table = 'pago';

    // Clave primaria personalizada
    protected $primaryKey = 'id_pago';

    // Campos asignables masivamente
    protected $fillable = [
        'fecha_pago',
        'pago',
        'monto',
        'comprobante',
        'numero_boleta',
        'tipo_pago',
        'id_habilitado',
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
    public function habilitado()
    {
        return $this->belongsTo(Habilitado::class, 'id_habilitado', 'id_habilitado');
    }

}
