<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carnet extends Model
{
    use HasFactory;

    protected $table = 'carnet';
    protected $primaryKey = 'id_carnet';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'doc',
        'discapacidad',
        'fecha_emision',
        'fecha_vencimiento',
        'id_persona',
    ];

    // ============================================================
    // TIMESTAMPS (zona horaria Bolivia)
    // ============================================================

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set('America/La_Paz');
        $this->attributes['created_at'] = Carbon::now();
    }

    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set('America/La_Paz');
        $this->attributes['updated_at'] = Carbon::now();
    }

    // ============================================================
    // RELACIONES
    // ============================================================

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeVencido($query)
    {
        return $query->where('fecha_vencimiento', '<', today());
    }

    public function scopeVigente($query)
    {
        return $query->where('fecha_vencimiento', '>=', today());
    }

    public function scopePorDiscapacidad($query, $tipo)
    {
        return $query->where('discapacidad', $tipo);
    }
}
