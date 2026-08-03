<?php

namespace App\Models;

class PresupuestoUsuario extends BaseModel
{
    protected $table = 'presupuesto_usuario';

    protected $fillable = [
        'id_usuario',
        'id_mes',
        'id_gestion',
        'monto_asignado',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function mes()
    {
        return $this->belongsTo(Mes::class, 'id_mes');
    }

    public function gestion()
    {
        return $this->belongsTo(Gestion::class, 'id_gestion');
    }
}
