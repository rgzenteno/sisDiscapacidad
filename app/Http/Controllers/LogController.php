<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class LogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('buscador');

        $logs = Activity::query()
            ->with(['causer', 'subject'])
            ->when($search, function ($query) use ($search) {

                // Buscar usuarios que coincidan
                $userIds = \App\Models\User::where('nombre',   'like', "%{$search}%")
                    ->orWhere('apellido', 'like', "%{$search}%")
                    ->orWhere('cargo',    'like', "%{$search}%")
                    ->orWhere('email',    'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(nombre, ' ', apellido) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("CONCAT(apellido, ' ', nombre) LIKE ?", ["%{$search}%"])
                    ->pluck('id');

                $query->where(function ($q) use ($search, $userIds) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereRaw(
                            "JSON_UNQUOTE(JSON_EXTRACT(properties, '$.module')) LIKE ?",
                            ["%{$search}%"]
                        )
                        ->orWhereRaw(
                            "JSON_UNQUOTE(JSON_EXTRACT(properties, '$.action')) LIKE ?",
                            ["%{$search}%"]
                        )
                        ->orWhere(function ($q) use ($userIds) {
                            $q->where('causer_type', 'App\\Models\\User')
                                ->whereIn('causer_id', $userIds->map(fn($id) => (string) $id));
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Logs/index', [
            'logs'    => $logs,
            'filters' => [
                'buscador' => $request->buscador ?? '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}