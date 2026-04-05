<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Mostrar formulario de edición de perfil
     */
    public function edit(Request $request): View
    {
        return view('profile.edit');
    }

    /**
     * Actualizar perfil del usuario
     */
    public function update(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            
            // Validar datos
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
                'avatar_data' => ['nullable', 'string'], // Base64 data URL
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);
            
            $user->name = $validated['name'];
            $user->username = $validated['username'];
            
            if (isset($validated['avatar_data'])) {
                // Validar que sea un data URL válido
                if (preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $validated['avatar_data'])) {
                    $user->avatar = $validated['avatar_data'];
                }
            }
            
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            
            $user->save();
            
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Perfil actualizado correctamente',
                    'avatar' => $user->avatar,
                    'name' => $user->name,
                ]);
            }
            
            return redirect()->route('profile.edit')->with('status', 'Perfil actualizado correctamente');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Error de validación', 'errors' => $e->errors()], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('api')->error('Profile update falló: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'user_id' => auth()->id()]);
            
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Error interno procesando el perfil.'], 500);
            }
            return back()->withErrors(['error' => 'Error del servidor, intente de nuevo más tarde']);
        }
    }

    /**
     * Actualizar solo el avatar del usuario (API) - Sin almacenamiento
     */
    public function updateAvatar(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'avatar_data' => ['required', 'string'], // Base64 data URL
            ]);

            $user = $request->user();
            $avatarData = $request->input('avatar_data');

            // Validar que sea un data URL válido
            if (!preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $avatarData)) {
                return response()->json(['success' => false, 'message' => 'Formato de imagen inválido'], 400);
            }

            // Guardar el data URL directamente en la base de datos
            $user->avatar = $avatarData;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Avatar actualizado',
                'avatar' => $avatarData
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Error de validación del avatar'], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('api')->error('updateAvatar falló: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'user_id' => auth()->id()]);
            return response()->json(['success' => false, 'message' => 'Error interno al actualizar el avatar.'], 500);
        }
    }

    /**
     * Eliminar cuenta del usuario
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}