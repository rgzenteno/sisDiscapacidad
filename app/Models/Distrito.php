<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_distrito
 * @property string $distrito
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distrito newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distrito newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distrito query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distrito whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distrito whereDistrito($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distrito whereIdDistrito($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Distrito whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Distrito extends Model
{
    use HasFactory;
    // Nombre de la tabla asociada
    protected $table = 'distrito';
    // Clave primaria personalizada
    protected $primaryKey = 'id_distrito';

    // Campos asignables masivamente
    protected $fillable = [
        'distrito',
    ];

    // Mutador para convertir automáticamente a mayúsculas
    // El nombre debe ser set + NombreDelCampo + Attribute
    public function setDistritoAttribute($value)
    {
        $this->attributes['distrito'] = strtoupper($value);
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