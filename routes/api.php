<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContactRequestController;

// Rutas API para el chat y contactos
Route::middleware('auth:sanctum')->group(function () {
    // Enviar solicitud de contacto
    Route::post('/contacts/request', [ChatController::class, 'sendContactRequest']);
    
    // Responder a solicitud de contacto (aceptar/denegar)
    Route::post('/contacts/request/respond', [ChatController::class, 'respondToRequest']);
    
    // Obtener solicitudes pendientes
    Route::get('/contacts/requests/pending', [ChatController::class, 'pendingRequests']);
    
    // Obtener lista de contactos
    Route::get('/contacts', [ChatController::class, 'getContacts']);
    
    // Enviar mensaje
    Route::post('/messages/send', [ChatController::class, 'sendMessage']);
    
    // Obtener mensajes con un contacto
    Route::get('/messages/{userId}', [ChatController::class, 'getMessages']);
});

// Ruta de prueba para verificar que la API funciona
Route::get('/test', function() {
    return response()->json(['message' => 'API funcionando correctamente']);
});
