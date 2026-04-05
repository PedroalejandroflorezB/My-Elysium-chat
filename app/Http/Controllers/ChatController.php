<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ContactRequest;
use App\Models\Message;
use App\Models\P2PSignal;
use App\Events\P2PSignalSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Mostrar vista principal del chat
     */
    public function index()
    {
        return view('chat.index');
    }

    /**
     * RUTA REAL: /chat/{username}
     * Solo para peticiones AJAX. Si es acceso directo, redirige a la amigable.
     */
    public function show($username, Request $request)
    {
        // 1. OBTENER CONTACTO Y MENSAJES
        $contact = User::where('username', $username)->firstOrFail();
        $currentUserId = auth()->id();
        
        $messages = Message::where(function($query) use ($currentUserId, $contact) {
                $query->where('sender_id', $currentUserId)
                      ->where('receiver_id', $contact->id)
                      ->where('deleted_for_sender', false);
            })
            ->orWhere(function($query) use ($currentUserId, $contact) {
                $query->where('sender_id', $contact->id)
                      ->where('receiver_id', $currentUserId)
                      ->where('deleted_for_receiver', false);
            })
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get();
            
        // ✅ CAPA 3: Lógica Dual (Si es AJAX retorna parcial, si no redirige)
        if ($request->attributes->get('is_ajax', false) || $request->ajax() || $request->wantsJson()) {
            
            // Si es JSON puro
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'contact' => $contact,
                    'messages' => $messages
                ]);
            }
            
            // Si es Fetch/AJAX para HTML (Navegación SPA)
            return view('chat.partials.chat-content', compact('contact', 'messages'));
        }
        
        // Redirigir a URL Amigable si se accede directamente a la real
        return redirect()->route('chat.friendly', ['username' => $username]);
    }

    /**
     * RUTA AMIGABLE: /@{username}
     * Carga el layout completo para navegación directa o F5.
     */
    public function showFriendly($username, Request $request)
    {
        $contact = User::where('username', $username)->firstOrFail();
        $currentUserId = auth()->id();
        
        $messages = Message::where(function($query) use ($currentUserId, $contact) {
                $query->where('sender_id', $currentUserId)
                      ->where('receiver_id', $contact->id)
                      ->where('deleted_for_sender', false);
            })
            ->orWhere(function($query) use ($currentUserId, $contact) {
                $query->where('sender_id', $contact->id)
                      ->where('receiver_id', $currentUserId)
                      ->where('deleted_for_receiver', false);
            })
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get();
            
        return view('chat.show', compact('contact', 'messages'));
    }

    /**
     * Enviar mensaje
     */
    public function sendMessage(Request $request)
    {
        try {
            $validated = $request->validate([
                'receiver_id' => 'required|integer|exists:users,id',
                'message' => 'required|string|max:10000'
            ]);
            
            $message = Message::create([
                'sender_id' => auth()->id(),
                'receiver_id' => $validated['receiver_id'],
                'message' => trim($validated['message']),
                'is_read' => false,
                'type' => 'text'
            ]);
            
            $message->load('sender:id,name,username,avatar');
            
            $roomId = $this->getRoomId(auth()->id(), $validated['receiver_id']);
            
            try {
                broadcast(new \App\Events\MessageSent($message, $roomId))->toOthers();
            } catch (\Exception $e) {
                \Log::warning('Broadcast fallido: ' . $e->getMessage());
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado',
                'data' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'message' => $message->message,
                    'is_read' => $message->is_read,
                    'type' => $message->type,
                    'created_at' => $message->created_at->toISOString(),
                    'sender' => [
                        'id' => auth()->user()->id,
                        'name' => auth()->user()->name,
                        'username' => auth()->user()->username,
                        'avatar' => auth()->user()->avatar ?? null,
                    ]
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Error en sendMessage: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno procesando el mensaje'
            ], 500);
        }
    }

    /**
     * Obtener mensajes de una conversación
     */
    public function getMessages($userId)
    {
        try {
            $messages = Message::where(function($q) use ($userId) {
                    $q->where('sender_id', auth()->id())
                      ->where('receiver_id', $userId);
                })
                ->orWhere(function($q) use ($userId) {
                    $q->where('sender_id', $userId)
                      ->where('receiver_id', auth()->id());
                })
                ->orderBy('created_at', 'asc')
                ->limit(100)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mensajes'
            ], 500);
        }
    }

    /**
     * Buscar usuarios
     */
    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|min:2|max:100'
            ]);
            
            $query = ltrim(trim($validated['query']), '@');
            
            $users = User::where('id', '!=', auth()->id())
                ->where(function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('username', 'LIKE', "%{$query}%")
                      ->orWhere('email', 'LIKE', "%{$query}%");
                })
                ->limit(10)
                ->get(['id', 'name', 'username', 'email', 'avatar']);
            
            $formattedUsers = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? null,
                    'initials' => strtoupper(substr($user->name, 0, 1)),
                ];
            });
            
            return response()->json([
                'success' => true,
                'results' => $formattedUsers,
                'count' => $formattedUsers->count(),
                'query' => $query
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Error en búsqueda: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor'
            ], 500);
        }
    }

    /**
     * Borrar chat
     */
    public function deleteChat(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'type' => 'required|in:me,all'
        ]);
        
        $currentUserId = auth()->id();
        $receiverId = $validated['receiver_id'];
        $roomId = $this->getRoomId($currentUserId, $receiverId);
        
        if($validated['type'] === 'all') {
            // Eliminar mensajes
            \App\Models\Message::where(function($q) use ($currentUserId, $receiverId) {
                    $q->where('sender_id', $currentUserId)
                      ->where('receiver_id', $receiverId);
                })
                ->orWhere(function($q) use ($currentUserId, $receiverId) {
                    $q->where('sender_id', $receiverId)
                      ->where('receiver_id', $currentUserId);
                })
                ->delete();
            
            // ✅ DISPARAR EVENTO (CRÍTICO)
            broadcast(new \App\Events\ChatDeleted($roomId, $currentUserId, $receiverId))->toOthers();
        } else {
            // Soft delete - solo para este usuario
            \App\Models\Message::where(function($q) use ($currentUserId, $receiverId) {
                    $q->where('sender_id', $currentUserId)
                      ->where('receiver_id', $receiverId);
                })
                ->orWhere(function($q) use ($currentUserId, $receiverId) {
                    $q->where('sender_id', $receiverId)
                      ->where('receiver_id', $currentUserId);
                })
                ->update([
                    'deleted_for_sender' => \DB::raw('CASE WHEN sender_id = ' . $currentUserId . ' THEN 1 ELSE deleted_for_sender END'),
                    'deleted_for_receiver' => \DB::raw('CASE WHEN receiver_id = ' . $currentUserId . ' THEN 1 ELSE deleted_for_receiver END')
                ]);
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Verificar status de usuario
     */
    public function getUserStatus($userId)
    {
        $lastSeen = cache()->get('user_last_seen_' . $userId);
        $isOnline = $lastSeen && (now()->diffInSeconds($lastSeen) < 60);
        
        return response()->json([
            'user_id' => $userId,
            'online' => $isOnline,
            'last_seen' => $lastSeen ? $lastSeen->toISOString() : null
        ]);
    }

    /**
     * Obtener lista de conversaciones para el sidebar
     */
    public function getConversationsList()
    {
        try {
            $currentUserId = auth()->id();
            
            // 1. Obtener todas las conversaciones que tengan al menos UN mensaje no borrado
            $conversations = \App\Models\Message::where(function($q) use ($currentUserId) {
                    $q->where('sender_id', $currentUserId)->where('deleted_for_sender', false);
                })
                ->orWhere(function($q) use ($currentUserId) {
                    $q->where('receiver_id', $currentUserId)->where('deleted_for_receiver', false);
                })
                ->selectRaw('LEAST(sender_id, receiver_id) as user1, GREATEST(sender_id, receiver_id) as user2, MAX(created_at) as last_activity')
                ->groupBy('user1', 'user2')
                ->orderBy('last_activity', 'desc')
                ->get();
            
            $formattedConversations = $conversations->map(function($conv) use ($currentUserId) {
                $otherUserId = $conv->user1 == $currentUserId ? $conv->user2 : $conv->user1;
                $otherUser = \App\Models\User::find($otherUserId);
                
                if (!$otherUser) return null;
                
                // 2. Obtener el mensaje más reciente que NO esté borrado para este usuario
                $lastMessage = \App\Models\Message::where(function($q) use ($conv) {
                        $q->where('sender_id', $conv->user1)->where('receiver_id', $conv->user2);
                    })
                    ->orWhere(function($q) use ($conv) {
                        $q->where('sender_id', $conv->user2)->where('receiver_id', $conv->user1);
                    })
                    ->where(function($q) use ($currentUserId) {
                        $q->where(function($sq) use ($currentUserId) {
                            $sq->where('sender_id', $currentUserId)->where('deleted_for_sender', false);
                        })->orWhere(function($sq) use ($currentUserId) {
                            $sq->where('receiver_id', $currentUserId)->where('deleted_for_receiver', false);
                        });
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if (!$lastMessage) return null;

                // 🔑 NUEVO: Contar mensajes no leídos en esta conversación
                $unreadCount = \App\Models\Message::where('sender_id', $otherUserId)
                    ->where('receiver_id', $currentUserId)
                    ->where('is_read', false)
                    ->count();
                
                return [
                    'id' => $otherUser->id,
                    'username' => $otherUser->username,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar ?? null,
                    'last_message' => \Str::limit($lastMessage->message, 40),
                    'last_message_time' => $lastMessage->created_at->format('H:i'),
                    'is_own_message' => $lastMessage->sender_id === $currentUserId,
                    'unread_count' => $unreadCount,
                ];
            })->filter()->values();
            
            // 🔑 NUEVO: Total de mensajes no leídos global
            $totalUnread = \App\Models\Message::where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->count();
            
            return response()->json([
                'success' => true, 
                'conversations' => $formattedConversations,
                'total_unread' => $totalUnread
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in ChatController::getConversationsList: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Error al cargar conversaciones'
            ], 500);
        }
    }

    /**
     * Marcar mensajes de un usuario específico como leídos
     */
    public function markAsRead($userId)
    {
        try {
            $currentUserId = auth()->id();
            
            // 1. Actualizar en DB
            $updated = \App\Models\Message::where('sender_id', $userId)
                ->where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            if ($updated > 0) {
                // 2. Disparar evento para que el emisor vea el "doble check"
                $roomId = $this->getRoomId($currentUserId, $userId);
                try {
                    broadcast(new \App\Events\MessagesMarkedAsRead($roomId, $userId, $currentUserId))->toOthers();
                } catch (\Exception $e) {
                    \Log::warning('Broadcast fallido en markAsRead: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'updated_count' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener TODOS los mensajes del usuario (Historial Global)
     */
    public function getAllMessages()
    {
        try {
            $currentUserId = auth()->id();
            $messages = Message::where(function($q) use ($currentUserId) {
                    $q->where('sender_id', $currentUserId)->where('deleted_for_sender', false);
                })
                ->orWhere(function($q) use ($currentUserId) {
                    $q->where('receiver_id', $currentUserId)->where('deleted_for_receiver', false);
                })
                ->with(['sender:id,name,username,avatar', 'receiver:id,name,username,avatar'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json(['success' => true, 'data' => $messages]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener lista de contactos confirmados
     */
    public function getContacts()
    {
        try {
            $contacts = \App\Models\Contact::where('user_id', auth()->id())
                ->with('contact:id,name,username,avatar')
                ->get();
            
            return response()->json(['success' => true, 'data' => $contacts]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Enviar solicitud de contacto
     */
    public function sendContactRequest(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'sometimes|integer|exists:users,id',
            'target_user_id' => 'sometimes|integer|exists:users,id'
        ]);

        $senderId = auth()->id();
        $receiverId = $validated['receiver_id'] ?? $validated['target_user_id'] ?? null;

        if (!$receiverId) {
            return response()->json(['success' => false, 'message' => 'ID de receptor no proporcionado'], 422);
        }

        // Verificar si ya son contactos
        $isContact = \App\Models\Contact::where('user_id', $senderId)
            ->where('contact_id', $receiverId)
            ->exists();

        if ($isContact) {
            return response()->json(['success' => false, 'message' => 'already_contact', 'detail' => 'Ya son contactos'], 422);
        }

        // Si ya hay una solicitud pendiente, eliminarla para permitir re-enviar la señal sin bloquear al usuario
        ContactRequest::where(function($q) use ($senderId, $receiverId) {
                $q->where('sender_id', $senderId)->where('receiver_id', $receiverId);
            })->orWhere(function($q) use ($senderId, $receiverId) {
                $q->where('sender_id', $receiverId)->where('receiver_id', $senderId);
            })->delete();

        $contactRequest = ContactRequest::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'status' => 'pending'
        ]);

        // ✅ DISPARAR EVENTO (REAL-TIME)
        $sender = auth()->user();
        try {
            broadcast(new \App\Events\ContactRequestSent($receiverId, [
                'request_id' => $contactRequest->id,
                'id' => $sender->id,
                'name' => $sender->name,
                'username' => $sender->username,
                'avatar' => $sender->avatar ?? null,
                'initials' => strtoupper(substr($sender->name, 0, 1))
            ]))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Broadcast fallido en sendContactRequest: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'data' => $contactRequest]);
    }

    /**
     * Obtener solicitudes pendientes
     */
    public function getPendingRequests()
    {
        $requests = ContactRequest::where('receiver_id', auth()->id())
            ->where('status', 'pending')
            ->with('sender:id,name,username,avatar')
            ->get();
        
        return response()->json(['success' => true, 'data' => $requests]);
    }

    /**
     * Responder a solicitud
     */
    public function respondToRequest(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'required|integer|exists:contact_requests,id',
            'action' => 'required|in:accept,reject'
        ]);

        $contactRequest = ContactRequest::findOrFail($validated['request_id']);
        
        if ($contactRequest->receiver_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($validated['action'] === 'accept') {
            $contactRequest->update(['status' => 'accepted']);
            
            // Crear relación bidireccional en Contacts
            \App\Models\Contact::firstOrCreate([
                'user_id' => $contactRequest->sender_id,
                'contact_id' => $contactRequest->receiver_id
            ]);
            \App\Models\Contact::firstOrCreate([
                'user_id' => $contactRequest->receiver_id,
                'contact_id' => $contactRequest->sender_id
            ]);
        } else {
            $contactRequest->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Eliminar contacto
     */
    public function removeContact(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|integer|exists:users,id'
        ]);

        $userId = auth()->id();
        $contactId = $validated['contact_id'];

        \App\Models\Contact::where(function($q) use ($userId, $contactId) {
            $q->where('user_id', $userId)->where('contact_id', $contactId);
        })->orWhere(function($q) use ($userId, $contactId) {
            $q->where('user_id', $contactId)->where('contact_id', $userId);
        })->delete();

        // Eliminar también posibles solicitudes cruzadas pendientes
        ContactRequest::where(function($q) use ($userId, $contactId) {
            $q->where('sender_id', $userId)->where('receiver_id', $contactId);
        })->orWhere(function($q) use ($userId, $contactId) {
            $q->where('sender_id', $contactId)->where('receiver_id', $userId);
        })->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Verificar estado de contacto
     */
    public function checkContactStatus($userId)
    {
        $isContact = \App\Models\Contact::where('user_id', auth()->id())
            ->where('contact_id', $userId)
            ->exists();
        
        $hasPending = ContactRequest::where('sender_id', auth()->id())
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->exists();

        return response()->json([
            'success' => true,
            'is_contact' => $isContact,
            'has_pending' => $hasPending
        ]);
    }

    /**
     * Obtener avatar de usuario
     */
    public function getUserAvatar($userId)
    {
        $user = User::findOrFail($userId);
        // Avatares son base64 — retornar directamente o placeholder
        if ($user->avatar && str_starts_with($user->avatar, 'data:')) {
            return response()->json(['avatar' => $user->avatar]);
        }
        return response()->json(['avatar' => null, 'initials' => strtoupper(substr($user->name, 0, 1))]);
    }

    /**
     * Polling: Obtener mensajes nuevos (Fallback)
     */
    public function getNewMessages(Request $request)
    {
        try {
            $currentUserId = auth()->id();
            
            $roomId = $request->query('room_id');
            
            // Obtener mensajes no leídos para este usuario
            $query = Message::where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->with('sender:id,name,username,avatar');

            // Opcional: Filtrar por sala si se especifica en la consulta
            if ($roomId && str_starts_with($roomId, 'chat_')) {
                // Una sala chat_1_2 implica que el emisor es el otro ID
                $parts = explode('_', $roomId);
                if (count($parts) === 3) {
                    $otherId = ($parts[1] == $currentUserId) ? $parts[2] : $parts[1];
                    $query->where('sender_id', $otherId);
                }
            }

            $messages = $query->orderBy('created_at', 'asc')->get();
                
            return response()->json([
                'success' => true,
                'messages' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Polling: Obtener estado de presencia de contactos (Fallback)
     */
    public function getPresenceStatus(Request $request)
    {
        try {
            $currentUserId = auth()->id();
            
            // Obtener IDs de contactos
            $contactIds = \App\Models\Contact::where('user_id', $currentUserId)
                ->pluck('contact_id');
                
            // Consultar presencia
            $users = User::whereIn('id', $contactIds)
                ->select('id', 'last_seen_at')
                ->get();
                
            $status = $users->mapWithKeys(function($user) {
                // Consideramos online si se vio en los últimos 2 minutos
                $isOnline = $user->last_seen_at && $user->last_seen_at->gt(now()->subMinutes(2));
                return [$user->id => [
                    'online' => $isOnline,
                    'last_seen' => $user->last_seen_at ? $user->last_seen_at->toISOString() : null
                ]];
            });
            
            return response()->json([
                'success' => true,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Enviar señal P2P (Backing storage + Broadcast)
     */
    public function sendP2PSignal(Request $request)
    {
        try {
            \Log::info('[P2P] Solicitude de señal recibida:', [
                'to' => $request->input('to'),
                'to_id' => $request->input('to_id'),
                'type' => $request->input('type'),
                'user_id' => auth()->id()
            ]);
            $toId = $request->input('to_id') ?? $request->input('to');
            $type = $request->input('type');
            $data = $request->input('data');

            if (!$toId || !$type || !$data) {
                 return response()->json(['success' => false, 'message' => 'Faltan parámetros críticos (to/to_id, type, data)'], 422);
            }

            $currentUserId = auth()->id();

            // 1. Persistir en DB para que el receptor pueda obtenerla vía polling
            $signal = P2PSignal::create([
                'from_id' => $currentUserId,
                'to_id' => $toId,
                'type' => $type,
                'data' => $data
            ]);

            // 2. Intentar broadcast en tiempo real
            try {
                broadcast(new P2PSignalSent($signal));
            } catch (\Exception $e) {
                \Log::warning('Fallback Polling Activo: Signal persistido pero broadcast falló: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'signal_id' => $signal->id
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Polling: Obtener nuevas señales P2P (Fallback)
     */
    public function getNewP2PSignals(Request $request)
    {
        try {
            $currentUserId = auth()->id();

            // Obtener señales no leídas destinadas a este usuario
            $signals = P2PSignal::where('to_id', $currentUserId)
                ->whereNull('read_at')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($signals->isNotEmpty()) {
                // Marcar como leídas inmediatamente
                P2PSignal::whereIn('id', $signals->pluck('id'))
                    ->update(['read_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'signals' => $signals
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener room ID único
     */
    private function getRoomId($user1, $user2): string
    {
        // ✅ RoomId consistente: chat_1_2 (NO chat_chat_1_2)
        $ids = collect([$user1, $user2])->sort()->implode('_');
        return "chat_{$ids}";
    }
}
