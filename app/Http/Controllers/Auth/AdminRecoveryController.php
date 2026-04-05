<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AdminRecoveryController extends Controller
{
    /**
     * Mostrar el formulario de recuperación de contraseña
     */
    public function showRecoveryForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Verificar el código de recuperación del admin
     */
    public function verifyRecoveryCode(Request $request)
    {
        $request->validate([
            'email'         => 'required|email|exists:users,email',
            'recovery_code' => ['required', 'string', 'regex:/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/'],
        ], [
            'email.exists'          => 'No existe una cuenta con este correo.',
            'recovery_code.regex'   => 'El código debe tener el formato XXXX-XXXX-XXXX.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->is_admin) {
            return back()->withErrors(['email' => 'Esta funcionalidad es solo para administradores.']);
        }

        // Rate limit: 3 intentos cada 2 horas
        $key = 'admin-recovery:' . $user->id;
        $attempts = \Cache::get($key . ':attempts', 0);

        if ($attempts >= 3) {
            $ttl = \Cache::get($key . ':ttl', now()->addHours(2));
            $minutesLeft = now()->diffInMinutes($ttl, false);
            if ($minutesLeft > 0) {
                return back()
                    ->withErrors(['recovery_code' => "Demasiados intentos. Intenta de nuevo en {$minutesLeft} minutos."])
                    ->withInput();
            }
            \Cache::forget($key . ':attempts');
            \Cache::forget($key . ':ttl');
            $attempts = 0;
        }

        // Verificar código contra los almacenados en caché para este admin
        $validCodes = \Cache::get("admin-recovery-codes:{$user->id}", []);
        $code = strtoupper($request->recovery_code);

        if (empty($validCodes) || !in_array($code, $validCodes)) {
            $attempts++;
            \Cache::put($key . ':attempts', $attempts, now()->addHours(2));
            if ($attempts === 1) {
                \Cache::put($key . ':ttl', now()->addHours(2), now()->addHours(2));
            }

            $remaining = 3 - $attempts;
            $msg = $remaining > 0
                ? 'Código incorrecto o ya utilizado.'
                : 'Demasiados intentos. Intenta de nuevo en 2 horas.';

            return back()->withErrors(['recovery_code' => $msg])->withInput();
        }

        // Código válido — marcarlo como usado (eliminarlo de la lista)
        $validCodes = array_values(array_filter($validCodes, fn($c) => $c !== $code));
        \Cache::put("admin-recovery-codes:{$user->id}", $validCodes, now()->addDays(30));

        // Limpiar rate limit
        \Cache::forget($key . ':attempts');
        \Cache::forget($key . ':ttl');

        session([
            'recovery_email'   => $request->email,
            'recovery_code'    => $code,
            'recovery_verified' => true,
            'recovery_type'    => 'admin',
            'recovery_expires' => now()->addMinutes(15),
        ]);

        return redirect()->route('admin.recovery.reset.form');
    }

    /**
     * Mostrar formulario de nueva contraseña
     */
    public function showResetForm()
    {
        // Verificar que la sesión de recuperación sea válida
        if (!session('recovery_verified') || 
            !session('recovery_expires') || 
            now()->gt(session('recovery_expires'))) {
            
            session()->forget(['recovery_email', 'recovery_code', 'recovery_verified', 'recovery_expires']);
            return redirect()->route('password.request')
                ->withErrors(['email' => 'La sesión de recuperación ha expirado. Inicia el proceso nuevamente.']);
        }

        // Redirigir al formulario existente de reset-password
        return view('auth.reset-password', [
            'request' => (object) ['email' => session('recovery_email')]
        ]);
    }

    /**
     * Restablecer la contraseña
     */
    public function resetPassword(Request $request)
    {
        // Verificar sesión
        if (!session('recovery_verified') || 
            !session('recovery_expires') || 
            now()->gt(session('recovery_expires'))) {
            
            session()->forget(['recovery_email', 'recovery_code', 'recovery_verified', 'recovery_expires']);
            return redirect()->route('password.request')
                ->withErrors(['email' => 'La sesión de recuperación ha expirado.']);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // Obtener usuario
        $user = User::where('email', session('recovery_email'))->first();
        
        if (!$user || !$user->is_admin) {
            session()->forget(['recovery_email', 'recovery_code', 'recovery_verified', 'recovery_expires']);
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Usuario no válido.']);
        }

        // Marcar el código como usado (esto se haría en una implementación real con base de datos)
        $this->markRecoveryCodeAsUsed($user->id, session('recovery_code'));

        // Actualizar contraseña
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Limpiar sesión
        session()->forget(['recovery_email', 'recovery_code', 'recovery_verified', 'recovery_expires']);

        return redirect()->route('login')
            ->with('status', 'Contraseña restablecida correctamente. Ya puedes iniciar sesión.');
    }

    /**
     * Validar código de recuperación
     * En una implementación real, esto verificaría contra una base de datos
     */
    private function validateRecoveryCode($userId, $code)
    {
        // Por ahora, simulamos la validación usando localStorage del navegador
        // En una implementación real, tendrías una tabla de códigos de recuperación
        
        // Verificar formato
        if (!preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code)) {
            return false;
        }

        // Verificar que no esté en la lista de códigos usados
        $usedCodes = session("used_recovery_codes_{$userId}", []);
        
        if (in_array($code, $usedCodes)) {
            return false;
        }

        // En una implementación real, aquí verificarías contra la base de datos
        // Por ahora, aceptamos cualquier código con el formato correcto
        return true;
    }

    /**
     * Verificar código de Gmail para usuarios regulares
     */
    public function verifyGmailCode(Request $request)
    {
        $request->validate([
            'email'      => 'required|email|exists:users,email',
            'gmail_code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ], [
            'gmail_code.regex' => 'El código debe tener 6 dígitos numéricos.',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->is_admin) {
            return back()->withErrors(['email' => 'Los administradores deben usar códigos de recuperación.']);
        }

        // Rate limit: 3 intentos, se reinicia cada 2 horas
        $key = 'gmail-recovery:' . $user->id;
        $attempts = \Cache::get($key . ':attempts', 0);

        if ($attempts >= 3) {
            $ttl = \Cache::get($key . ':ttl', now()->addHours(2));
            $minutesLeft = now()->diffInMinutes($ttl, false);
            if ($minutesLeft > 0) {
                return back()
                    ->withErrors(['gmail_code' => "Demasiados intentos fallidos. Intenta de nuevo en {$minutesLeft} minutos."])
                    ->withInput();
            }
            // Reiniciar si ya pasaron las 2 horas
            \Cache::forget($key . ':attempts');
            \Cache::forget($key . ':ttl');
            $attempts = 0;
        }

        // Verificar código en caché (válido 10 minutos)
        $storedCode = \Cache::get("gmail-code:{$user->id}");

        if (!$storedCode || $storedCode !== $request->gmail_code) {
            $attempts++;
            \Cache::put($key . ':attempts', $attempts, now()->addHours(2));
            if ($attempts === 1) {
                \Cache::put($key . ':ttl', now()->addHours(2), now()->addHours(2));
            }

            $remaining = 3 - $attempts;
            $errorMsg = $remaining > 0
                ? 'Código incorrecto o expirado.'
                : 'Demasiados intentos fallidos. Intenta de nuevo en 2 horas.';

            return back()->withErrors(['gmail_code' => $errorMsg])->withInput()
                ->with('attempts', $attempts);
        }

        // Código válido — limpiar rate limit y código
        \Cache::forget($key . ':attempts');
        \Cache::forget($key . ':ttl');
        \Cache::forget("gmail-code:{$user->id}");

        session([
            'recovery_email'   => $request->email,
            'recovery_verified' => true,
            'recovery_type'    => 'gmail',
            'recovery_expires' => now()->addMinutes(15),
        ]);

        return redirect()->route('admin.recovery.reset.form');
    }

    /**
     * Enviar código de recuperación por Gmail (válido 10 minutos)
     */
    public function sendGmailCode(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->is_admin) {
            return response()->json(['success' => false, 'message' => 'Correo no válido.'], 422);
        }

        // Rate limit envío: máx 3 envíos cada 2 horas
        $sendKey = 'gmail-send:' . $user->id;
        if (\Cache::get($sendKey, 0) >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Límite de envíos alcanzado. Intenta en 2 horas.'
            ], 429);
        }

        // Generar código de 6 dígitos y guardarlo 10 minutos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        \Cache::put("gmail-code:{$user->id}", $code, now()->addMinutes(10));
        \Cache::increment($sendKey);
        \Cache::put($sendKey, \Cache::get($sendKey, 1), now()->addHours(2));

        try {
            \Mail::to($user->email)->send(new \App\Mail\GmailRecoveryMail($user->name, $code));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('GmailRecovery send error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al enviar el correo.'], 500);
        }
    }

    /**
     * Guardar códigos de recuperación del admin en caché (llamado desde el panel)
     */
    public function saveRecoveryCodes(Request $request)
    {
        $request->validate([
            'codes'   => 'required|array|min:1|max:20',
            'codes.*' => ['required', 'string', 'regex:/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/'],
        ]);

        $user = auth()->user();

        if (!$user || !$user->is_admin) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        // Guardar en caché por 30 días
        \Cache::put(
            "admin-recovery-codes:{$user->id}",
            array_map('strtoupper', $request->codes),
            now()->addDays(30)
        );

        return response()->json(['success' => true]);
    }

    /**
     * Marcar código como usado (legacy - ahora se maneja en verifyRecoveryCode)
     */
    private function markRecoveryCodeAsUsed($userId, $code)
    {
        // Los códigos se eliminan de caché en verifyRecoveryCode
    }
}