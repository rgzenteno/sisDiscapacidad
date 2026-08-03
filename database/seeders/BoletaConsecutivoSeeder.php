<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BoletaConsecutivoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lee el último correlativo de cada mes/gestión desde los pagos existentes
        $resultados = DB::table('pago')
            ->join('habilitado', 'pago.id_habilitado', '=', 'habilitado.id_habilitado')
            ->whereNotNull('pago.numero_boleta')
            ->selectRaw('
            habilitado.id_mes,
            habilitado.id_gestion,
            MAX(CAST(SUBSTRING_INDEX(pago.numero_boleta, "-", -1) AS UNSIGNED)) as ultimo
        ')
            ->groupBy('habilitado.id_mes', 'habilitado.id_gestion')
            ->get();

        foreach ($resultados as $fila) {
            DB::table('boleta_consecutivo')->insertOrIgnore([
                'id_mes'        => $fila->id_mes,
                'id_gestion'    => $fila->id_gestion,
                'ultimo_numero' => $fila->ultimo,
            ]);
        }

        echo "Consecutivos cargados: {$resultados->count()} períodos.\n";
    }
}
