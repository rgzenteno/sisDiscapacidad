<?php

namespace App\Services;

use App\Models\Gestion;
use App\Models\Habilitado;
use App\Models\HistorialEstados;
use App\Models\Mes;
use App\Models\Persona;
use App\Models\RetroactivoEvaluacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Str;

class RetroactivoService
{
    private const ESTADOS_EXCLUYENTES = ['baja_temporal', 'baja_definitiva', 'depurado'];

    /**
     * `mes.mes` de los meses normales es el mes calendario real (1=Enero...12=Diciembre),
     * sin desfase — confirmado con datos reales (mes=1 de la gestión 2026 contiene
     * la lista de enero 2026, no diciembre 2025). El ciclo de retro de SIGEP va de
     * diciembre a noviembre, cruzando dos gestiones: diciembre pertenece a la
     * gestión ANTERIOR (año - 1, su propio mes=12), y enero-noviembre pertenecen
     * a la gestión que se está cerrando (mes 1-11). Esta función traduce la
     * posición dentro del ciclo retro (1-12) al mes calendario real, usado como
     * `mes_original` en el mes-retro.
     */
    public function mesOriginalDesdePosicion(int $posicion): int
    {
        return $posicion === 1 ? 12 : $posicion - 1;
    }

    /**
     * Año calendario real al que corresponde una posición dentro de la gestión.
     */
    public function anioCalendarioDesdePosicion(Gestion $gestion, int $posicion): int
    {
        return $posicion === 1 ? $gestion->gestion - 1 : $gestion->gestion;
    }

    /**
     * Estado del proceso de retroactivos para una gestión: si el apartado de
     * carga está habilitado (activado manualmente por un superusuario vía
     * `gestion.retroactivos_habilitado` — no se infiere de la cantidad de
     * meses normales cargados, porque esperar a que exista un mes específico
     * como noviembre podría dejar la funcionalidad inalcanzable si ese mes se
     * atrasa), cuál es la siguiente posición a procesar (secuencial, de
     * enero = posición 2 a octubre = posición 11 — diciembre y noviembre
     * quedan fuera de la carga de retro), y los meses-retro ya creados.
     */
    public function estado(int $idGestion): array
    {
        $gestion = Gestion::findOrFail($idGestion);
        $habilitado = (bool) $gestion->retroactivos_habilitado;

        $retrosCreados = Mes::where('id_gestion', $idGestion)
            ->where('es_retroactivo', true)
            ->orderBy('id_mes')
            ->get(['id_mes', 'mes', 'mes_original', 'monto', 'presupuesto', 'sin_retroactivo'])
            ->map(function ($mesRetro) {
                $mesRetro->posicion = $mesRetro->mes_original === 12 ? 1 : $mesRetro->mes_original + 1;
                return $mesRetro;
            });

        // Solo se cargan retroactivos de enero a octubre (posiciones 2 a 11
        // del ciclo SIGEP) — diciembre (posición 1) y noviembre (posición 12)
        // quedan fuera del alcance de esta carga. Se crean en orden estricto:
        // la posición N+1 solo está disponible cuando ya existen N meses-retro.
        $siguientePosicion = null;

        if ($habilitado && $retrosCreados->count() < 10) {
            $siguientePosicion = 2 + $retrosCreados->count();
        }

        return [
            'habilitado' => $habilitado,
            'siguiente_posicion' => $siguientePosicion,
            'siguiente_mes_original' => $siguientePosicion
                ? $this->mesOriginalDesdePosicion($siguientePosicion)
                : null,
            'retros_creados' => $retrosCreados,
        ];
    }

    /**
     * Activa/desactiva manualmente el apartado de carga de retroactivos para
     * una gestión. Es un switch único por gestión (no por mes): mientras esté
     * en false, el apartado no se muestra en la vista, sin importar cuántos
     * meses normales tenga cargados.
     */
    public function toggleHabilitado(int $idGestion): bool
    {
        $gestion = Gestion::findOrFail($idGestion);
        $gestion->retroactivos_habilitado = !$gestion->retroactivos_habilitado;
        $gestion->save();

        return $gestion->retroactivos_habilitado;
    }

    /**
     * Resumen de un mes-retro ya creado: cuántos habilitados tiene, cuántos
     * ya cobraron, cuántos faltan, y cómo va el presupuesto asignado frente a
     * lo ya pagado. Versión reducida del resumen que se usa para los meses
     * normales (solo lo que aplica a un mes-retro).
     */
    public function detalleMesRetro(int $idMes): array
    {
        $mes = Mes::where('id_mes', $idMes)->where('es_retroactivo', true)->firstOrFail();

        if ($mes->sin_retroactivo) {
            return [
                'mes_original' => $mes->mes_original,
                'sin_retroactivo' => true,
                'monto' => (float) $mes->monto,
                'presupuesto' => 0.0,
                'cantidad_habilitados' => 0,
                'cantidad_pagados' => 0,
                'cantidad_no_pagados' => 0,
                'cantidad_bajas' => 0,
                'total_procesados' => 0,
                'total_pagado' => 0.0,
                'presupuesto_restante' => 0.0,
            ];
        }

        $cantidadHabilitados = DB::table('habilitado')
            ->where('id_mes', $idMes)
            ->where('habilitado', 1)
            ->count();

        $cantidadPagados = DB::table('pago')
            ->join('habilitado', 'pago.id_habilitado', '=', 'habilitado.id_habilitado')
            ->where('habilitado.id_mes', $idMes)
            ->where('pago.pago', 1)
            ->count();

        $totalPagado = (float) DB::table('pago')
            ->join('habilitado', 'pago.id_habilitado', '=', 'habilitado.id_habilitado')
            ->where('habilitado.id_mes', $idMes)
            ->where('pago.pago', 1)
            ->sum('pago.monto');

        // Las bajas de este mes-retro nunca tienen `habilitado` — salen de
        // retroactivo_evaluacion. Se suman a los habilitados para poder
        // corroborar contra el total de filas del Excel que se cargó.
        $cantidadBajas = DB::table('retroactivo_evaluacion')
            ->where('id_gestion', $mes->id_gestion)
            ->where('mes_original', $mes->mes_original)
            ->where(function ($q) {
                $q->whereNotNull('estado_baja')->orWhere('es_correccion_manual', true);
            })
            ->count();

        return [
            'mes_original' => $mes->mes_original,
            'sin_retroactivo' => false,
            'monto' => (float) $mes->monto,
            'presupuesto' => (float) $mes->presupuesto,
            'cantidad_habilitados' => $cantidadHabilitados,
            'cantidad_pagados' => $cantidadPagados,
            'cantidad_no_pagados' => max(0, $cantidadHabilitados - $cantidadPagados),
            'cantidad_bajas' => $cantidadBajas,
            'total_procesados' => $cantidadHabilitados + $cantidadBajas,
            'total_pagado' => $totalPagado,
            'presupuesto_restante' => (float) $mes->presupuesto - $totalPagado,
        ];
    }

    /**
     * Presupuesto sugerido para el mes-retro: para cada CI de la lista,
     * determina si va a ser habilitado por este retro o si es una baja (según
     * la sección 4 del diseño, en este orden de prioridad):
     *   1. Si ya tiene un pago VÁLIDO en el mes normal correspondiente → baja
     *      ("ya pagado"), para no duplicar el pago.
     *   2. Si no existe en el sistema → habilitado (se crea como persona
     *      nueva al confirmar).
     *   3. Si su estado vigente al cierre de ese mes histórico es
     *      baja_temporal / baja_definitiva / depurado → baja.
     *   4. Si no, está activo → habilitado.
     * `total_cis` = `habilitados` + `bajas` siempre, para que quede claro que
     * ningún registro del archivo se pierde. Es de solo lectura, no crea nada
     * todavía (eso pasa en confirmarCargaRetro).
     *
     * $registros: array de ['ci' => string, 'nombre' => string]
     */
    public function calcularPresupuestoSugerido(int $idGestion, int $posicion, array $registros): array
    {
        $gestion = Gestion::findOrFail($idGestion);
        $mesNormal = $this->mesNormalOrigen($gestion, $posicion);

        $mesOriginal = $this->mesOriginalDesdePosicion($posicion);
        $anioCalendario = $this->anioCalendarioDesdePosicion($gestion, $posicion);
        $fechaCorte = Carbon::create($anioCalendario, $mesOriginal, 1)->endOfMonth()->toDateString();

        $registrosLimpios = $this->limpiarRegistros($registros);
        $cis = $registrosLimpios->pluck('ci');

        $personasPorCi = Persona::whereIn('ci_persona', $cis)->pluck('id_persona', 'ci_persona');
        $idsPersona = $personasPorCi->values()->all();
        $estadosVigentes = $this->estadosVigentesEnFecha($idsPersona, $fechaCorte);
        $habilitadosNormales = $this->habilitadosNormalesVigentes($idsPersona, $mesNormal->id_mes);
        $personasConAntecedenteBaja = $this->personasConAntecedenteBaja($idsPersona);

        $habilitados = [];
        $yaPagados = [];
        $yaHabilitados = [];
        $bajas = [];

        foreach ($registrosLimpios as $registro) {
            $ci = $registro['ci'];
            $nombre = $registro['nombre'];
            $idPersona = $personasPorCi[$ci] ?? null;

            if (!$idPersona) {
                $habilitados[] = ['ci' => $ci, 'nombre' => $nombre, 'nuevo' => true];
                continue;
            }

            $habilitadoNormal = $habilitadosNormales[$idPersona] ?? null;

            if ($habilitadoNormal) {
                if ($habilitadoNormal->numero_boleta) {
                    $yaPagados[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => "Ya pagado en el mes normal (boleta {$habilitadoNormal->numero_boleta})"];
                } else {
                    $yaHabilitados[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => 'Ya habilitado en el mes normal, pendiente de pago'];
                }
                continue;
            }

            $registroVigente = $estadosVigentes[$idPersona] ?? null;
            $estado = $registroVigente->estado ?? null;

            if ($estado && in_array($estado, self::ESTADOS_EXCLUYENTES)) {
                $real = $this->resolverEstadoRealExclusion($idPersona, $estado, $registroVigente->fecha_inicio, $registroVigente->id);
                $motivo = $real['anomalia']
                    ? 'Depurado sin baja previa registrada (revisar historial)'
                    : $this->motivoEstado($real['estado']);

                $bajas[] = ['ci' => $ci, 'nombre' => $nombre, 'motivo' => $motivo];
                continue;
            }

            // Aunque el historial diga que hoy está activo, si en algún
            // momento tuvo una baja_temporal/baja_definitiva se marca para
            // que quien carga el retro lo vea aparte y pueda excluirlo a
            // mano si corresponde (ej. reincorporado por falta de respaldo
            // de fallecimiento, no por certeza de que está vivo).
            $habilitados[] = [
                'ci' => $ci,
                'nombre' => $nombre,
                'nuevo' => false,
                'tiene_baja_previa' => in_array($idPersona, $personasConAntecedenteBaja, true),
            ];
        }

        $totalHabilitados = count($habilitados);
        $monto = (int) $mesNormal->monto;

        return [
            'mes_original' => $mesOriginal,
            'monto' => $monto,
            'total_cis' => $cis->count(),
            'total_habilitados' => $totalHabilitados,
            'habilitados' => $habilitados,
            'ya_pagados' => $yaPagados,
            'ya_habilitados' => $yaHabilitados,
            'bajas' => $bajas,
            'presupuesto_sugerido' => $totalHabilitados * $monto,
        ];
    }

    /**
     * Confirma la carga del mes-retro de la siguiente posición disponible:
     * crea la fila `mes` (offset +100) + `boleta_consecutivo`, y por cada
     * registro del archivo:
     *   - si el CI no existe en `persona`, lo crea (ci + nombre) junto con su
     *     `historial_estados` inicial en 'activo' (fecha_inicio = primer día
     *     del mes histórico), igual que hace addMes con CIs nuevos.
     *   - si ya tiene un pago VÁLIDO en el mes normal correspondiente (sección
     *     4 del diseño, caso "ya pagado en enero"), no crea `habilitado` —
     *     evita el doble pago para quien ya cobró normalmente ese mes.
     *   - si no, determina el estado vigente en ese mes histórico; si es
     *     baja_temporal/baja_definitiva/depurado, no crea `habilitado`; si no,
     *     crea el `habilitado` con id_mes = mes-retro.
     *
     * $registros: array de ['ci' => string, 'nombre' => string]
     */
    public function confirmarCargaRetro(
        int $idGestion,
        int $posicion,
        int $presupuesto,
        array $registros,
        int $userId,
        string $nombreUsuario,
        array $exclusionesManuales = []
    ): array {
        $estado = $this->estado($idGestion);

        if (!$estado['habilitado']) {
            throw new Exception('El apartado de retroactivos no está habilitado para esta gestión.');
        }

        if ($estado['siguiente_posicion'] !== $posicion) {
            throw new Exception("Se esperaba crear la posición {$estado['siguiente_posicion']} del ciclo de retroactivos, no la posición {$posicion}. Deben cargarse en orden.");
        }

        $gestion = Gestion::findOrFail($idGestion);
        $mesNormal = $this->mesNormalOrigen($gestion, $posicion);
        $mesOriginal = $this->mesOriginalDesdePosicion($posicion);
        $anioCalendario = $this->anioCalendarioDesdePosicion($gestion, $posicion);
        $fechaPrimerDia = Carbon::create($anioCalendario, $mesOriginal, 1)->format('Y-m-d');
        $fechaCorte = Carbon::create($anioCalendario, $mesOriginal, 1)->endOfMonth()->toDateString();
        $nuevoValorMes = 100 + $mesOriginal;

        $registrosLimpios = $this->limpiarRegistros($registros);

        return DB::transaction(function () use (
            $idGestion,
            $mesNormal,
            $mesOriginal,
            $nuevoValorMes,
            $presupuesto,
            $registrosLimpios,
            $fechaPrimerDia,
            $fechaCorte,
            $userId,
            $nombreUsuario,
            $exclusionesManuales
        ) {
            $mesRetro = Mes::create([
                'mes' => $nuevoValorMes,
                'mes_original' => $mesOriginal,
                'es_retroactivo' => true,
                'monto' => $mesNormal->monto,
                'presupuesto' => $presupuesto,
                'id_gestion' => $idGestion,
            ]);

            DB::table('boleta_consecutivo')->insertOrIgnore([
                'id_mes' => $mesRetro->id_mes,
                'id_gestion' => $idGestion,
                'ultimo_numero' => 0,
            ]);

            $cisExistentes = Persona::whereIn('ci_persona', $registrosLimpios->pluck('ci'))
                ->pluck('id_persona', 'ci_persona');

            $habilitadosNormales = $this->habilitadosNormalesVigentes($cisExistentes->values()->all(), $mesNormal->id_mes);

            $personasNuevas = 0;
            $habilitadosCreados = 0;
            $excluidos = [];

            foreach ($registrosLimpios as $registro) {
                $ci = $registro['ci'];
                $nombre = $registro['nombre'] ?: $ci;
                $idPersona = $cisExistentes[$ci] ?? null;
                $estadoVigente = null;
                $historialVigente = null;

                if (!$idPersona) {
                    $idPersona = (string) Str::uuid();

                    Persona::create([
                        'id_persona' => $idPersona,
                        'nombre_completo' => $nombre,
                        'ci_persona' => $ci,
                        'complemento' => $registro['complemento'] ?? null,
                        'tipo_registro' => 'pendiente',
                        'fecha_registro' => $fechaPrimerDia,
                    ]);

                    HistorialEstados::create([
                        'id_persona' => $idPersona,
                        'estado' => 'activo',
                        'fecha_inicio' => $fechaPrimerDia,
                        'fecha_fin' => null,
                        'fecha_registro' => $fechaPrimerDia,
                        'usuario_modificacion' => $nombreUsuario,
                        'observaciones' => 'Alta generada desde carga de retroactivo.',
                    ]);

                    $cisExistentes[$ci] = $idPersona;
                    $personasNuevas++;
                    $estadoVigente = 'activo';
                } elseif ($habilitadoNormal = ($habilitadosNormales[$idPersona] ?? null)) {
                    // Ya existe un `habilitado` = 1 para esta persona en el mes normal
                    // (pagado o no). Nunca se crea uno nuevo por retro encima — la
                    // regla de negocio exige que jamás existan dos `habilitado` = 1
                    // simultáneos (normal + retro) para la misma persona/mes. No se
                    // persiste en retroactivo_evaluacion: esa tabla queda reservada
                    // solo para bajas (ver más abajo) — este caso es reconstruible
                    // comparando el Excel cargado contra el habilitado/pago del mes
                    // normal, no hace falta duplicarlo en una tabla aparte.
                    $excluidos[] = $habilitadoNormal->numero_boleta
                        ? ['ci' => $ci, 'motivo' => "Ya pagado en el mes normal (boleta {$habilitadoNormal->numero_boleta})"]
                        : ['ci' => $ci, 'motivo' => 'Ya habilitado en el mes normal, pendiente de pago'];

                    continue;
                } else {
                    $historialVigente = HistorialEstados::where('id_persona', $idPersona)
                        ->where('fecha_inicio', '<=', $fechaCorte)
                        ->orderByDesc('fecha_inicio')
                        ->orderByDesc('id')
                        ->first();
                    $estadoVigente = $historialVigente?->estado;
                }

                if ($estadoVigente && in_array($estadoVigente, self::ESTADOS_EXCLUYENTES)) {
                    $real = $this->resolverEstadoRealExclusion($idPersona, $estadoVigente, $historialVigente?->fecha_inicio, $historialVigente?->id);

                    $excluidos[] = [
                        'ci' => $ci,
                        'motivo' => $real['anomalia'] ? 'Depurado sin baja previa registrada' : $this->motivoEstado($real['estado']),
                    ];

                    // retroactivo_evaluacion queda estrictamente para bajas: solo se
                    // persiste cuando la causa real es baja_temporal/baja_definitiva.
                    // El caso anómalo (depurado sin que se encuentre la baja previa
                    // que lo causó) no se guarda — solo queda en la respuesta de esta
                    // carga para revisión manual inmediata, igual que "ya pagado"/"ya
                    // habilitado" arriba.
                    if (!$real['anomalia']) {
                        RetroactivoEvaluacion::create([
                            'id_persona' => $idPersona,
                            'id_gestion' => $idGestion,
                            'mes_original' => $mesOriginal,
                            'estado_baja' => $real['estado'],
                            'motivo' => "Excluido de retroactivo: {$real['estado']} vigente desde " . ($real['fecha_inicio'] ?? 'fecha desconocida'),
                        ]);
                    }

                    continue;
                }

                // El estado vigente dice que corresponde pagar, pero quien
                // cargó el retro decidió excluirlo a mano en el preview (ej.
                // antecedente de baja que el historial ya no refleja como
                // vigente). No se toca el estado real de la persona — solo se
                // evita crear el habilitado-retro, dejando constancia de que
                // fue una decisión humana, no automática.
                if (array_key_exists($ci, $exclusionesManuales)) {
                    $excluidos[] = ['ci' => $ci, 'motivo' => "Excluido manualmente: {$exclusionesManuales[$ci]}"];

                    RetroactivoEvaluacion::create([
                        'id_persona' => $idPersona,
                        'id_gestion' => $idGestion,
                        'mes_original' => $mesOriginal,
                        'estado_baja' => null,
                        'es_correccion_manual' => true,
                        'usuario_correccion' => $nombreUsuario,
                        'motivo' => "Excluido manualmente: {$exclusionesManuales[$ci]}",
                    ]);

                    continue;
                }

                // No se persiste en retroactivo_evaluacion cuando es pagable: el
                // `habilitado` recién creado (cruzado con `mes.es_retroactivo` y
                // `mes.mes_original`) ya es la fuente de verdad completa para saber
                // quién cobra por este retro — no hace falta duplicarlo.
                Habilitado::create([
                    'habilitado' => 1,
                    'id' => $userId,
                    'id_persona' => $idPersona,
                    'id_gestion' => $idGestion,
                    'id_mes' => $mesRetro->id_mes,
                    'fecha_habilitado' => now(),
                ]);

                $habilitadosCreados++;
            }

            return [
                'mes_retro' => $mesRetro,
                'personas_nuevas' => $personasNuevas,
                'habilitados_creados' => $habilitadosCreados,
                'excluidos' => $excluidos,
                'total_procesados' => $registrosLimpios->count(),
            ];
        });
    }

    /**
     * Confirma que el mes de la siguiente posición del ciclo no tuvo ninguna
     * lista de retro que procesar (SIGEP no envió pendientes ese mes). Crea
     * igual la fila `mes`-retro + su `boleta_consecutivo` en 0 — únicamente
     * para destrabar la secuencia, sin registros de por medio — pero no
     * procesa personas ni crea ningún `habilitado`. Es un camino
     * explícitamente distinto de `confirmarCargaRetro` (no "una carga con 0
     * registros") para que quede claro en los logs que fue una decisión
     * consciente y no un archivo vacío por error de lectura.
     */
    public function confirmarSinRetroactivo(int $idGestion, int $posicion): array
    {
        $estado = $this->estado($idGestion);

        if (!$estado['habilitado']) {
            throw new Exception('El apartado de retroactivos no está habilitado para esta gestión.');
        }

        if ($estado['siguiente_posicion'] !== $posicion) {
            throw new Exception("Se esperaba crear la posición {$estado['siguiente_posicion']} del ciclo de retroactivos, no la posición {$posicion}. Deben cargarse en orden.");
        }

        $gestion = Gestion::findOrFail($idGestion);
        $mesOriginal = $this->mesOriginalDesdePosicion($posicion);
        $mesNormal = $this->mesNormalOrigen($gestion, $posicion);
        $nuevoValorMes = 100 + $mesOriginal;

        return DB::transaction(function () use ($idGestion, $mesNormal, $mesOriginal, $nuevoValorMes) {
            $mesRetro = Mes::create([
                'mes' => $nuevoValorMes,
                'mes_original' => $mesOriginal,
                'es_retroactivo' => true,
                'sin_retroactivo' => true,
                'monto' => $mesNormal->monto,
                'presupuesto' => 0,
                'id_gestion' => $idGestion,
            ]);

            DB::table('boleta_consecutivo')->insertOrIgnore([
                'id_mes' => $mesRetro->id_mes,
                'id_gestion' => $idGestion,
                'ultimo_numero' => 0,
            ]);

            return [
                'mes_retro' => $mesRetro,
                'personas_nuevas' => 0,
                'habilitados_creados' => 0,
                'excluidos' => [],
                'total_procesados' => 0,
            ];
        });
    }

    /**
     * Texto corto y legible para un estado excluyente, usado tanto en el
     * resumen de la vista previa como en el log de la carga confirmada.
     */
    private function motivoEstado(string $estado): string
    {
        return match ($estado) {
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'depurado' => 'Depurado',
            default => ucfirst(str_replace('_', ' ', $estado)),
        };
    }

    /**
     * Para cada persona de `$idsPersona` que ya tenga un `habilitado` = 1
     * VIGENTE en el mes normal `$idMesNormal` (pagado o no), devuelve una fila
     * con `numero_boleta` (no nulo solo si además tiene un pago VÁLIDO
     * asociado). Se usa para excluir del retro a quien ya está habilitado
     * normalmente ese mes — la regla de negocio no permite que existan dos
     * `habilitado` = 1 simultáneos (normal + retro) para la misma persona:
     *   - Con `numero_boleta` → ya pagado, no se duplica el pago.
     *   - Sin `numero_boleta` → ya habilitado pero pendiente de pago; se
     *     excluye igual (evita el segundo `habilitado` = 1) y se muestra
     *     aparte para que el usuario lo verifique manualmente — si en
     *     realidad corresponde procesarlo por retro, primero debe poner en 0
     *     el `habilitado` del mes normal y recién ahí recalcular.
     */
    private function habilitadosNormalesVigentes(array $idsPersona, int $idMesNormal): \Illuminate\Support\Collection
    {
        if (empty($idsPersona)) {
            return collect();
        }

        return DB::table('habilitado')
            ->leftJoin('pago', function ($join) {
                $join->on('pago.id_habilitado', '=', 'habilitado.id_habilitado')
                    ->where('pago.pago', 1);
            })
            ->where('habilitado.id_mes', $idMesNormal)
            ->where('habilitado.habilitado', 1)
            ->whereIn('habilitado.id_persona', $idsPersona)
            ->select('habilitado.id_persona', 'pago.numero_boleta')
            ->get()
            ->keyBy('id_persona');
    }

    /**
     * Si el estado vigente es 'depurado', resuelve la baja real que lo causó
     * (baja_temporal o baja_definitiva, el registro inmediatamente anterior
     * en el historial) — 'depurado' es un estado interno que el sistema usa
     * para filtrar a quien no aparece en el PDF normal, pero de cara al
     * usuario y a los reportes de retro lo que importa es la baja real
     * detrás, nunca 'depurado' en sí. Si el estado vigente ya es una baja
     * directa (no depurado), se devuelve tal cual.
     *
     * Devuelve ['estado' => ..., 'fecha_inicio' => ..., 'anomalia' => bool].
     * `anomalia` = true cuando un 'depurado' no tiene una baja real detrás
     * (nunca debería pasar dado que la depuración solo se aplica sobre
     * alguien que ya estaba de baja — se marca para revisión manual en vez
     * de ocultarse u asumirse como baja).
     */
    private function resolverEstadoRealExclusion(string $idPersona, string $estadoVigente, ?string $fechaInicioVigente, ?int $idVigente): array
    {
        if ($estadoVigente !== 'depurado') {
            return ['estado' => $estadoVigente, 'fecha_inicio' => $fechaInicioVigente, 'anomalia' => false];
        }

        $query = HistorialEstados::where('id_persona', $idPersona);

        if ($fechaInicioVigente !== null && $idVigente !== null) {
            $query->where(function ($q) use ($fechaInicioVigente, $idVigente) {
                $q->where('fecha_inicio', '<', $fechaInicioVigente)
                    ->orWhere(function ($q2) use ($fechaInicioVigente, $idVigente) {
                        $q2->where('fecha_inicio', $fechaInicioVigente)->where('id', '<', $idVigente);
                    });
            });
        }

        $anterior = $query->orderByDesc('fecha_inicio')->orderByDesc('id')->first();

        if (!$anterior || !in_array($anterior->estado, ['baja_temporal', 'baja_definitiva'])) {
            return ['estado' => 'depurado', 'fecha_inicio' => $fechaInicioVigente, 'anomalia' => true];
        }

        return ['estado' => $anterior->estado, 'fecha_inicio' => $anterior->fecha_inicio, 'anomalia' => false];
    }

    private function estadosVigentesEnFecha(array $idsPersona, string $fechaCorte): \Illuminate\Support\Collection
    {
        if (empty($idsPersona)) {
            return collect();
        }

        return DB::table('historial_estados as he')
            ->select('he.id', 'he.id_persona', 'he.estado', 'he.fecha_inicio')
            ->whereIn('he.id_persona', $idsPersona)
            ->where('he.fecha_inicio', '<=', $fechaCorte)
            ->whereNotExists(function ($sub) use ($fechaCorte) {
                $sub->select(DB::raw(1))
                    ->from('historial_estados as he2')
                    ->whereColumn('he2.id_persona', 'he.id_persona')
                    ->where('he2.fecha_inicio', '<=', $fechaCorte)
                    ->where(function ($q) {
                        $q->whereColumn('he2.fecha_inicio', '>', 'he.fecha_inicio')
                            ->orWhere(function ($q2) {
                                $q2->whereColumn('he2.fecha_inicio', '=', 'he.fecha_inicio')
                                    ->whereColumn('he2.id', '>', 'he.id');
                            });
                    });
            })
            ->get()
            ->keyBy('id_persona');
    }

    /**
     * `id_persona` de quienes alguna vez tuvieron baja_temporal/baja_definitiva
     * en su historial, sin importar la fecha ni si ya volvieron a activo. Sirve
     * para marcar en el preview a alguien que hoy figura pagable pero tiene un
     * antecedente de baja — no se excluye automáticamente (el estado vigente
     * ya decide eso), solo se señala para que quien carga el retro lo revise.
     */
    private function personasConAntecedenteBaja(array $idsPersona): array
    {
        if (empty($idsPersona)) {
            return [];
        }

        return DB::table('historial_estados')
            ->whereIn('id_persona', $idsPersona)
            ->whereIn('estado', ['baja_temporal', 'baja_definitiva'])
            ->distinct()
            ->pluck('id_persona')
            ->all();
    }

    private function limpiarRegistros(array $registros): \Illuminate\Support\Collection
    {
        return collect($registros)
            ->map(function ($r) {
                [$ci, $complemento] = $this->separarCiComplemento((string) ($r['ci'] ?? ''));

                return [
                    'ci' => mb_substr($ci, 0, 10),
                    'complemento' => $complemento !== '' ? mb_substr($complemento, 0, 2) : null,
                    'nombre' => trim((string) ($r['nombre'] ?? '')),
                ];
            })
            ->filter(fn($r) => $r['ci'] !== '')
            ->unique('ci')
            ->values();
    }

    /**
     * El C.I. del Excel puede venir con complemento pegado, ej. "8786023 - 1B"
     * o "8786023-1B". `ci_persona` (10) y `complemento` (2) son columnas
     * separadas en `persona`, así que hay que dividirlo antes de guardar.
     */
    private function separarCiComplemento(string $valor): array
    {
        $valor = trim($valor);

        if (str_contains($valor, '-')) {
            [$ci, $complemento] = array_pad(explode('-', $valor, 2), 2, '');
            return [trim($ci), trim($complemento)];
        }

        return [$valor, ''];
    }

    /**
     * Fila `mes` normal de donde se hereda el `monto` real. Para la posición 1
     * (diciembre) es el mes=12 de la gestión ANTERIOR (año - 1) — diciembre no
     * pertenece a la gestión que se está cerrando. Para las posiciones 2-12
     * (enero a noviembre) es el mes correspondiente de la gestión actual.
     */
    private function mesNormalOrigen(Gestion $gestionCerrando, int $posicion): Mes
    {
        if ($posicion === 1) {
            $anioAnterior = $gestionCerrando->gestion - 1;
            $gestionAnterior = Gestion::where('gestion', $anioAnterior)->first();

            if (!$gestionAnterior) {
                throw new Exception("No existe la gestión {$anioAnterior}; se necesita su mes de diciembre para crear el retroactivo de diciembre.");
            }

            return Mes::where('id_gestion', $gestionAnterior->id_gestion)
                ->where('es_retroactivo', false)
                ->where('mes', 12)
                ->firstOrFail();
        }

        $mesOriginal = $this->mesOriginalDesdePosicion($posicion);

        return Mes::where('id_gestion', $gestionCerrando->id_gestion)
            ->where('es_retroactivo', false)
            ->where('mes', $mesOriginal)
            ->firstOrFail();
    }
}
