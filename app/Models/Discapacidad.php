<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Discapacidad extends BaseModel
{
    use HasFactory;

    protected $table = 'discapacidad';
    protected $primaryKey = 'id_discapacidad';

    protected $fillable = [
        'discapacidad',
    ];

    public function setDiscapacidadAttribute(string $value): void
    {
        $this->attributes['discapacidad'] = strtoupper($value);
    }
}
