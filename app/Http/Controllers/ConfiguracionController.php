<?php

namespace App\Http\Controllers;

use App\Models\Parametro;
use App\Services\LogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Configuración de los logos institucionales (sacaba.png / sigamos.png,
 * usados en todos los reportes PDF y Excel; logo.png, usado como favicon y
 * en la pantalla de carga), y de los parámetros generales del sistema (ej.
 * Monto - Persona). Solo superusuario (ver routes/web.php: middleware
 * permission:superusuario).
 *
 * Los archivos se guardan SIEMPRE con el mismo nombre fijo, sobrescribiendo
 * el anterior — así no hace falta tocar ningún composable PDF/Excel ni la
 * referencia al favicon cuando cambian de gestión.
 */
class ConfiguracionController extends Controller
{
    /**
     * Nombre de archivo fijo y carpeta destino (relativa a public/) de cada
     * logo editable, mapeados a la clave que usa el formulario del frontend.
     */
    private const LOGOS = [
        'sacaba' => ['archivo' => 'sacaba.png', 'dir' => 'images'],
        'sigamos' => ['archivo' => 'sigamos.png', 'dir' => 'images'],
        'logo' => ['archivo' => 'logo.png', 'dir' => ''],
    ];

    /**
     * Reglas de validación por `tipo` de parámetro. Cada tipo tiene un
     * propósito fijo que no cambia (ej. "Monto - Persona" siempre es el
     * monto usado al crear una gestión), así que la regla va atada al
     * nombre exacto de `tipo` en vez de inferirse del valor. Cualquier
     * tipo que no esté acá cae al fallback genérico de `actualizarParametro`.
     */
    private const REGLAS_POR_TIPO = [
        'Monto - Persona' => ['required', 'numeric', 'min:0'],
        'Color - Sistema' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
    ];

    protected LogService $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function index()
    {
        $logos = [];
        foreach (self::LOGOS as $clave => $info) {
            $rutaRelativa = trim("{$info['dir']}/{$info['archivo']}", '/');
            $path = public_path($rutaRelativa);
            $logos[$clave] = [
                'url' => file_exists($path) ? asset($rutaRelativa) . '?v=' . filemtime($path) : null,
                'actualizado' => file_exists($path) ? date('d/m/Y H:i', filemtime($path)) : null,
            ];
        }

        return Inertia::render('Configuracion/index', [
            'logos' => $logos,
            'parametros' => Parametro::orderBy('tipo')->get(['id', 'tipo', 'valor']),
        ]);
    }

    /**
     * Actualiza el valor de un parámetro general del sistema (ej. Monto -
     * Persona). No se toca el "tipo" acá — solo se crean/renombran parámetros
     * directamente por migración, para no exponer eso a error de tipeo desde
     * el frontend.
     */
    public function actualizarParametro(Request $request, Parametro $parametro)
    {
        $reglas = self::REGLAS_POR_TIPO[$parametro->tipo] ?? ['required', 'string', 'max:255'];

        $validated = $request->validate([
            'valor' => $reglas,
        ]);

        $valorAnterior = $parametro->valor;
        $parametro->update(['valor' => $validated['valor']]);

        $user = $request->user();
        $nombre = ucwords(strtolower("{$user->nombre} {$user->apellido}"));

        $this->logService->logUpdate(
            'ConfiguracionParametro',
            $parametro,
            null,
            "El usuario {$nombre} actualizó el parámetro '{$parametro->tipo}' de '{$valorAnterior}' a '{$parametro->valor}'."
        );

        return back();
    }

    public function update(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'in:' . implode(',', array_keys(self::LOGOS))],
            // Se exige PNG (mismo formato que ya usan todos los composables
            // PDF / Exports que apuntan a sacaba.png / sigamos.png, y el
            // favicon/preloader que apuntan a logo.png) para no tener que
            // tocar código cuando cambien de gestión.
            'archivo' => ['required', 'image', 'mimes:png', 'max:2048'],
        ]);

        $clave = $request->input('logo');
        $info = self::LOGOS[$clave];
        $nombreFijo = $info['archivo'];
        $destino = $info['dir'] ? public_path($info['dir']) : public_path();
        $rutaDestino = "{$destino}/{$nombreFijo}";

        // Igual patrón que DigitalSignatureController::update() — borrar el
        // archivo anterior antes de guardar el nuevo (cubre además el caso
        // de que el archivo subido tenga otra extensión, aunque acá se
        // valida PNG estrictamente y el nombre final siempre es el mismo).
        if (file_exists($rutaDestino)) {
            unlink($rutaDestino);
        }

        $file = $request->file('archivo');
        $file->move($destino, $nombreFijo);

        $user = $request->user();
        $nombre = ucwords(strtolower("{$user->nombre} {$user->apellido}"));

        $this->logService->logUpdate(
            'ConfiguracionLogo',
            $user,
            null,
            "El usuario {$nombre} actualizó el logo '{$nombreFijo}'."
        );

        return back()->with('success', 'Logo actualizado correctamente.');
    }
}
