<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Agrega este "use"
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_carnet
 * @property string $doc
 * @property string $discapacidad
 * @property string $fecha_emision
 * @property string $fecha_vencimiento
 * @property int $id_persona
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Persona $persona
 * @method static \Database\Factories\CarnetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet whereDiscapacidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet whereDoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet whereFechaEmision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet whereFechaVencimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet whereIdCarnet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet whereIdPersona($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Carnet whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
