<?php

namespace App\Models;

class Parametro extends BaseModel
{
    protected $table = 'parametro';

    protected $fillable = [
        'tipo',
        'valor',
    ];

    /**
     * Valor actual configurado para un tipo de parámetro (ej. 'Monto - Persona').
     * Null si todavía no fue configurado por el superusuario.
     */
    public static function valorDe(string $tipo): ?string
    {
        return static::where('tipo', $tipo)->value('valor');
    }
}
