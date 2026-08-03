<?php

namespace App\Models;

// Extiende BaseModel (no Model directo) para que created_at se serialice
// como 'Y-m-d H:i:s' — mismo formato que ya usan Pago y el resto de los
// modelos del sistema, y el que espera formatDateTime() en el frontend
// (ver listaHabilitados.vue, bloque "Aviso de anulación").
class PagoAnulacion extends BaseModel
{
    protected $table = 'pago_anulacion';

    protected $fillable = [
        'id_pago',
        'observaciones',
        'usuario_modificacion',
    ];

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'id_pago', 'id_pago');
    }
}
