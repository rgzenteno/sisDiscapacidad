<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DigitalSignatureController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'signature' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = $request->user();

        // Eliminar firma anterior si existe
        if ($user->digital_signature && file_exists(public_path($user->digital_signature))) {
            unlink(public_path($user->digital_signature));
        }

        // Guardar nueva firma directamente en public/firmas_digitales
        $file = $request->file('signature');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('firmas_digitales'), $filename);
        
        $path = 'firmas_digitales/' . $filename;

        // Actualizar usuario
        $user->update([
            'digital_signature' => $path,
        ]);

        return back();
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->digital_signature && file_exists(public_path($user->digital_signature))) {
            unlink(public_path($user->digital_signature));
            
            $user->update([
                'digital_signature' => null,
            ]);
        }

        return back();
    }
}