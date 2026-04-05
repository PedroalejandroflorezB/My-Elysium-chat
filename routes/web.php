<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactFormController;

// Formulario de contacto público (hero home)
Route::post('/contact', [ContactFormController::class, 'send'])->name('contact.send');

Route::middleware(['auth'])->group(function () {
    // API Contactos (agrupado)
    Route::prefix('api/contacts')->group(function () {
        Route::get('/check/{userId}', [ContactController::class, 'check']);
        Route::post('/add', [ContactController::class, 'add']);
        Route::delete('/remove/{userId}', [ContactController::class, 'remove']);
    });
});

/*
|--------------------------------------------------------------------------
| Web Routes - Elysium Ito
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('chat.index');
    }
    return view('welcome');
})->name('home');

// Ruta para agregar usuario por QR (pública)
Route::get('/add/{username}', function ($username) {
    if (!auth()->check()) {
        return redirect()->route('login')->with('message', 'Inicia sesión para agregar contactos');
    }
    
    $user = \App\Models\User::where('username', $username)->first();
    if (!$user) {
        return redirect()->route('chat.index')->with('error', 'Usuario no encontrado');
    }
    
    if ($user->id === auth()->id()) {
        return redirect()->route('chat.index')->with('info', 'No puedes agregarte a ti mismo');
    }
    
    // Verificar si ya son contactos
    $isContact = auth()->user()->contacts()->where('contact_id', $user->id)->exists();
    if ($isContact) {
        return redirect()->route('chat.show', $username)->with('info', 'Ya tienes a este usuario como contacto');
    }
    
    // Verificar si ya hay una solicitud pendiente
    $pendingRequest = \App\Models\ContactRequest::where(function($query) use ($user) {
        $query->where('sender_id', auth()->id())->where('receiver_id', $user->id);
    })->orWhere(function($query) use ($user) {
        $query->where('sender_id', $user->id)->where('receiver_id', auth()->id());
    })->where('status', 'pending')->first();
    
    if ($pendingRequest) {
        return redirect()->route('chat.index')->with('info', 'Ya existe una solicitud de contacto pendiente con este usuario');
    }
    
    // Crear solicitud de contacto automáticamente
    \App\Models\ContactRequest::create([
        'sender_id' => auth()->id(),
        'receiver_id' => $user->id,
        'status' => 'pending'
    ]);
    
    return redirect()->route('chat.index')->with('success', "Solicitud de contacto enviada a {$user->name} (@{$user->username})");
})->name('add.user');

// Auth::routes(); // Redundante con Breeze (auth.php)

Route::middleware(['auth'])->group(function () {
    
    // ===== VISTAS =====
    // /dashboard solo accesible para admins
    Route::get('/dashboard', function () {
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('chat.index');
        }
        return view('dashboard');
    })->name('dashboard');
    
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    
    // Ruta para cargar modal de seguridad dinámicamente
    Route::get('/chat/security-modal', function () {
        return view('chat.partials.modals.security');
    })->name('chat.security-modal');
    
    // ✅ RUTA AMIGABLE (Para navegación directa)
    Route::get('/@{username}', [ChatController::class, 'showFriendly'])->name('chat.friendly');

    // ✅ RUTA REAL (Protegida: Solo AJAX o redirección en el controlador)
    Route::get('/chat/{username}', [ChatController::class, 'show'])->name('chat.show');
    

    

    // ===== API OPTIMIZADA =====
    Route::prefix('api')->middleware('throttle:elysium-api')->group(function () {

        // Mensajes — throttle extra en envío para evitar spam
        Route::prefix('messages')->group(function () {
            Route::post('/send', [ChatController::class, 'sendMessage'])->middleware('throttle:elysium-messages');
            Route::get('/{userId}', [ChatController::class, 'getMessages']);
            Route::get('/all', [ChatController::class, 'getAllMessages']);
            Route::post('/read/all/{userId}', [ChatController::class, 'markAsRead']);
        });

        // Contactos
        Route::prefix('contacts')->group(function () {
            Route::get('/list', [ChatController::class, 'getContacts']);
            Route::post('/search', [ChatController::class, 'search']);
            Route::post('/request', [ChatController::class, 'sendContactRequest'])->middleware('throttle:elysium-messages');
            Route::post('/respond', [ChatController::class, 'respondToRequest']);
            Route::post('/remove', [ChatController::class, 'removeContact']);
            Route::get('/status/{userId}', [ChatController::class, 'checkContactStatus']);
        });

        // Otros
        Route::get('/conversations-list', [ChatController::class, 'getConversationsList']);
        Route::get('/pending-requests', [ChatController::class, 'getPendingRequests']);
        Route::post('/chat/delete', [ChatController::class, 'deleteChat']);
        Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);
        Route::get('/user/{userId}/status', [ChatController::class, 'getUserStatus']);
        Route::get('/user/{userId}/avatar', [ChatController::class, 'getUserAvatar']);

        // --- RUTAS DE FALLBACK (POLLING) ---
        Route::get('/messages/new', [ChatController::class, 'getNewMessages']);
        Route::get('/presence/status', [ChatController::class, 'getPresenceStatus']);
        Route::prefix('p2p')->group(function () {
            Route::post('/signal', [ChatController::class, 'sendP2PSignal'])->middleware('throttle:elysium-p2p');
            Route::get('/signals/new', [ChatController::class, 'getNewP2PSignals']);
        });
    });



});

Route::middleware(['auth', 'verified', 'isAdmin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard principal - redirige a usuarios
    Route::get('/', function () {
        return redirect()->route('admin.users.index');
    })->name('dashboard');

    // Gestión CRUD de Usuarios
    Route::resource('users', UserController::class)->except(['show']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

