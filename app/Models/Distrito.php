<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Distrito extends BaseModel
{
    use HasFactory;

    protected $table = 'distrito';
    protected $primaryKey = 'id_distrito';

    protected $fillable = [
        'distrito',
    ];

    public function setDistritoAttribute(string $value): void
    {
        $this->attributes['distrito'] = strtoupper($value);
    }
}
