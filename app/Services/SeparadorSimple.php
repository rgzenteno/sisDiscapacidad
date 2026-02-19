<?php

namespace App\Services;

class SeparadorSimple
{
    public static function separar(string $nombreCompleto): array
    {
        $nombreCompleto = trim($nombreCompleto);
        $partes = explode(' ', $nombreCompleto);
        $total = count($partes);

        if ($total <= 1) {
            return [
                'nombre' => $nombreCompleto,
                'apellido' => '',
            ];
        }

        if ($total == 2) {
            return [
                'nombre' => $partes[1],
                'apellido' => $partes[0],
            ];
        }

        // 3+ palabras: última palabra = nombre
        $ultimaPalabra = end($partes);
        
        if (strlen($ultimaPalabra) <= 3 && $total >= 3) {
            $nombre = implode(' ', array_slice($partes, -2));
            $apellido = implode(' ', array_slice($partes, 0, -2));
        } else {
            $nombre = $ultimaPalabra;
            $apellido = implode(' ', array_slice($partes, 0, -1));
        }

        return [
            'nombre' => $nombre,
            'apellido' => $apellido,
        ];
    }
}