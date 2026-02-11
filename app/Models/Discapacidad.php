<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_discapacidad
 * @property string $discapacidad
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Discapacidad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Discapacidad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Discapacidad query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Discapacidad whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Discapacidad whereDiscapacidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Discapacidad whereIdDiscapacidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Discapacidad whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Discapacidad extends Model
{
    //

    use HasFactory;
    // Nombre de la tabla asociada
    protected $table = 'discapacidad';
    // Clave primaria personalizada
    protected $primaryKey = 'id_discapacidad';

    // Campos asignables masivamente
    protected $fillable = [
        'discapacidad',
    ];

    // Mutador para convertir automáticamente a mayúsculas
    // El nombre debe ser set + NombreDelCampo + Attribute
    public function setDiscapacidadAttribute($value)
    {
        $this->attributes['discapacidad'] = strtoupper($value);
    }

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
}