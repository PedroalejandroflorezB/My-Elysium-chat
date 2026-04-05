<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Contact;
use App\Models\ContactRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    /**
     * Verificar si un usuario es contacto
     */
    public function check($userId): JsonResponse
    {
        $isContact = auth()->user()->contacts()->where('contact_id', $userId)->exists();
        
        return response()->json([
            'success' => true,
            'is_contact' => $isContact
        ]);
    }

    /**
     * Añadir un contacto
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'username' => 'sometimes|exists:users,username'
        ]);

        $currentUserId = auth()->id();
        $userId = null;

        // Determinar el ID del usuario a agregar
        if ($request->has('user_id')) {
            $userId = $request->user_id;
        } elseif ($request->has('username')) {
            $user = User::where('username', $request->username)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }
            $userId = $user->id;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Debes proporcionar user_id o username'
            ], 400);
        }

        // Verificar que no se agregue a sí mismo
        if ($userId == $currentUserId) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes agregarte a ti mismo'
            ], 400);
        }

        // Verificar si ya es contacto
        $existingContact = Contact::where('user_id', $currentUserId)
            ->where('contact_id', $userId)
            ->first();

        if ($existingContact) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuario ya es tu contacto'
            ], 400);
        }

        // Crear la relación de contacto (bidireccional)
        Contact::create([
            'user_id' => $currentUserId,
            'contact_id' => $userId
        ]);

        Contact::create([
            'user_id' => $userId,
            'contact_id' => $currentUserId
        ]);

        // Obtener información del usuario agregado
        $addedUser = User::find($userId);

        return response()->json([
            'success' => true,
            'message' => 'Contacto agregado exitosamente',
            'user' => [
                'id' => $addedUser->id,
                'name' => $addedUser->name,
                'username' => $addedUser->username
            ]
        ]);
    }

    /**
     * Eliminar un contacto
     */
    public function remove($userId): JsonResponse
    {
        $currentUserId = auth()->id();

        // Eliminar ambas direcciones de la relación
        Contact::where('user_id', $currentUserId)
            ->where('contact_id', $userId)
            ->delete();

        Contact::where('user_id', $userId)
            ->where('contact_id', $currentUserId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contacto eliminado exitosamente'
        ]);
    }
}