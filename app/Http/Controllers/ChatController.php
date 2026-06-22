<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Exibe a página principal do chat
     */
    public function index()
    {
        $user = Auth::user();
        
        // Busca todas as conversas do usuário (como criador ou assinante)
        $conversations = Conversation::where(function($query) use ($user) {
            $query->where('creator_id', $user->id)
                  ->orWhere('subscriber_id', $user->id);
        })
        ->with(['creator', 'subscriber'])
        ->orderBy('last_message_at', 'desc')
        ->get()
        ->map(function($conversation) use ($user) {
            $otherParticipant = $conversation->getOtherParticipant($user->id);
            $lastMessage = $conversation->lastMessage();
            $unreadCount = $conversation->unreadCount($user->id);
            
            return [
                'id' => $conversation->id,
                'other_participant' => [
                    'id' => $otherParticipant->id,
                    'name' => $otherParticipant->name,
                    'username' => $otherParticipant->username,
                    'profile_photo' => $otherParticipant->profile_photo_url,
                    'creator_status' => $otherParticipant->creator_status,
                ],
                'last_message' => $lastMessage ? [
                    'content' => $lastMessage->content,
                    'message_type' => $lastMessage->message_type,
                    'created_at' => $lastMessage->created_at,
                ] : null,
                'unread_count' => $unreadCount,
                'last_message_at' => $conversation->last_message_at,
            ];
        });

        return view('chat.index', compact('conversations'));
    }

    /**
     * Inicia ou retorna uma conversa existente
     * Se o usuário atual é criador, $userId é o assinante
     * Se o usuário atual é assinante, $userId é o criador
     */
    public function startConversation($userId)
    {
        $user = Auth::user();
        $otherUser = \App\Models\User::findOrFail($userId);

        // Se o usuário atual é criador aprovado
        if ($user->creator_status === 'approved') {
            // Validação: verifica se o outro usuário é assinante ativo
            $hasActiveSubscription = Subscription::where('user_id', $otherUser->id)
                ->where('creator_id', $user->id)
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->exists();

            if (!$hasActiveSubscription) {
                return redirect()->route('chat.index')->with('error', 'Este usuário não é seu assinante ativo.');
            }

            // Busca ou cria a conversa (criador = $user, assinante = $otherUser)
            $conversation = Conversation::firstOrCreate(
                [
                    'creator_id' => $user->id,
                    'subscriber_id' => $otherUser->id,
                ],
                [
                    'last_message_at' => now(),
                ]
            );
        } else {
            // Se o usuário atual é assinante
            // Validação: o outro usuário deve ser criador aprovado
            if ($otherUser->creator_status !== 'approved') {
                return redirect()->route('chat.index')->with('error', 'Este usuário não é um criador aprovado.');
            }

            // Validação: verifica se tem assinatura ativa
            $hasActiveSubscription = Subscription::where('user_id', $user->id)
                ->where('creator_id', $otherUser->id)
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->exists();

            if (!$hasActiveSubscription) {
                return redirect()->route('chat.index')->with('error', 'Você precisa estar assinando este criador para iniciar uma conversa.');
            }

            // Busca ou cria a conversa (criador = $otherUser, assinante = $user)
            $conversation = Conversation::firstOrCreate(
                [
                    'creator_id' => $otherUser->id,
                    'subscriber_id' => $user->id,
                ],
                [
                    'last_message_at' => now(),
                ]
            );
        }

        // Redireciona para o chat
        return redirect()->route('chat.show', $conversation->id);
    }

    /**
     * Exibe uma conversa específica
     */
    public function show($conversationId)
    {
        $user = Auth::user();
        
        $conversation = Conversation::with(['creator', 'subscriber'])
            ->findOrFail($conversationId);

        // Verifica se o usuário tem permissão para ver esta conversa
        if ($conversation->creator_id !== $user->id && $conversation->subscriber_id !== $user->id) {
            abort(403, 'Você não tem permissão para acessar esta conversa.');
        }

        // Marca todas as mensagens não lidas como lidas
        Message::where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $otherParticipant = $conversation->getOtherParticipant($user->id);
        
        // Busca todas as mensagens da conversa
        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('chat.show', compact('conversation', 'otherParticipant', 'messages'));
    }

    /**
     * Envia uma nova mensagem
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $user = Auth::user();
        
        $conversation = Conversation::findOrFail($conversationId);

        // Verifica permissão
        if ($conversation->creator_id !== $user->id && $conversation->subscriber_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para enviar mensagens nesta conversa.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required_without:image|string|max:5000',
            'image' => 'required_without:content|image|mimes:jpeg,jpg,png,gif|max:10240', // 10MB
        ], [
            'content.required_without' => 'A mensagem precisa ter conteúdo ou uma imagem.',
            'image.required_without' => 'A mensagem precisa ter conteúdo ou uma imagem.',
            'image.image' => 'O arquivo deve ser uma imagem.',
            'image.max' => 'A imagem não pode ter mais de 10MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $messageType = 'text';
        $content = $request->input('content');
        $filePath = null;

        // Se houver imagem
        if ($request->hasFile('image')) {
            $messageType = 'image';
            $file = $request->file('image');
            $filePath = $file->store('chat/' . $conversation->id, 'public');
            $content = null; // Para imagens, o conteúdo pode ser null ou uma descrição
        }

        // Cria a mensagem
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'message_type' => $messageType,
            'content' => $content,
            'file_path' => $filePath,
        ]);

        // Atualiza last_message_at da conversa
        $conversation->update(['last_message_at' => now()]);

        // Retorna a mensagem criada
        return response()->json([
            'success' => true,
            'message' => $message->load('user'),
        ]);
    }

    /**
     * Busca novas mensagens (polling)
     */
    public function getNewMessages(Request $request, $conversationId)
    {
        $user = Auth::user();
        
        $conversation = Conversation::findOrFail($conversationId);

        // Verifica permissão
        if ($conversation->creator_id !== $user->id && $conversation->subscriber_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para acessar esta conversa.',
            ], 403);
        }

        // Timestamp da última mensagem recebida (do cliente)
        $lastMessageId = $request->input('last_message_id', 0);

        // Busca apenas mensagens novas (após o ID informado)
        $newMessages = Message::where('conversation_id', $conversation->id)
            ->where('id', '>', $lastMessageId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        // Marca as mensagens recebidas como lidas (se não foram enviadas pelo próprio usuário)
        foreach ($newMessages as $message) {
            if ($message->user_id !== $user->id && !$message->read_at) {
                $message->markAsRead();
            }
        }

        return response()->json([
            'success' => true,
            'messages' => $newMessages,
        ]);
    }

    /**
     * Exibe a tela de nova mensagem (seleção de usuários)
     */
    public function newMessage()
    {
        $user = Auth::user();
        
        // Se for criador, busca apenas seus assinantes
        // Se for assinante, busca apenas criadores que ele assina
        if ($user->creator_status === 'approved') {
            // Criador: busca assinantes ativos através das subscriptions onde creator_id = $user->id
            $subscriptionUserIds = \App\Models\Subscription::where('creator_id', $user->id)
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->pluck('user_id')
                ->unique();
            
            $availableUsers = \App\Models\User::whereIn('id', $subscriptionUserIds)->get();
        } else {
            // Assinante: busca criadores que ele assina
            $subscriptionCreatorIds = \App\Models\Subscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->pluck('creator_id')
                ->unique();
            
            $availableUsers = \App\Models\User::whereIn('id', $subscriptionCreatorIds)
                ->where('creator_status', 'approved')
                ->get();
        }

        return view('chat.new-message', compact('availableUsers'));
    }

    /**
     * Busca usuários para nova mensagem (AJAX)
     */
    public function searchUsers(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search', '');
        $onlineOnly = $request->input('online_only', false);

        $query = null;

        if ($user->creator_status === 'approved') {
            // Criador: busca assinantes ativos através das subscriptions
            $subscriptionUserIds = \App\Models\Subscription::where('creator_id', $user->id)
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->pluck('user_id')
                ->unique();
            
            $query = \App\Models\User::whereIn('id', $subscriptionUserIds);
        } else {
            // Assinante: busca criadores que ele assina
            $subscriptionCreatorIds = \App\Models\Subscription::where('user_id', $user->id)
                ->where('is_active', true)
                ->where('end_date', '>=', now())
                ->pluck('creator_id')
                ->unique();
            
            $query = \App\Models\User::whereIn('id', $subscriptionCreatorIds)
                ->where('creator_status', 'approved');
        }

        // Busca por nome ou username
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filtro de online (mock - pode ser implementado com last_activity)
        // Por enquanto, retorna todos

        $users = $query->get()->map(function($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'profile_photo' => $u->profile_photo_url,
                'creator_status' => $u->creator_status,
            ];
        });

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }
}
