<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactFormController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // Rate limit: máx 3 envíos por IP cada 10 minutos
        $key = 'contact-form:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos. Intenta en {$seconds} segundos."
            ], 429);
        }
        RateLimiter::hit($key, 600); // 10 minutos

        $adminEmail = env('MAIL_ADMIN_ADDRESS', env('MAIL_FROM_ADDRESS'));

        try {
            Mail::to($adminEmail)->send(new ContactFormMail(
                senderName:  $request->name,
                senderEmail: $request->email,
                message:     $request->message
            ));

            return response()->json([
                'success' => true,
                'message' => '¡Mensaje enviado! Te responderemos pronto.'
            ]);
        } catch (\Exception $e) {
            \Log::error('ContactForm error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar. Intenta de nuevo más tarde.'
            ], 500);
        }
    }
}
