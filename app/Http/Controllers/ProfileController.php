<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Mostra o perfil do usuário
     * Aceita dois formatos:
     * - /{username} - perfil do criador
     * - /{referrerSlug}/{creatorSlug} - perfil do criador com indicador
     */
    public function show($username, $creatorSlug = null)
    {
        // Se o username for "me" e o usuário estiver autenticado, redireciona para o próprio perfil
        if ($username === 'me' && Auth::check()) {
            if (!Auth::user()->slug) {
                return redirect()->route('profile.edit')->with('info', 'Por favor, defina um username primeiro.');
            }
            return redirect()->route('profile.show', Auth::user()->slug);
        }
        
        $referrerSlug = null;
        $actualCreatorSlug = $username;
        
        // Se há dois parâmetros, o primeiro é o referrer e o segundo é o creator
        if ($creatorSlug !== null) {
            $referrerSlug = $username;
            $actualCreatorSlug = $creatorSlug;
            
            // Se usuário não autenticado acessa link de afiliado, redireciona para cadastro
            // A indicação vale para qualquer assinatura, mas salva creator_slug para redirecionamento após cadastro
            if (!Auth::check()) {
                // Armazena o referrer_slug (indicação vale para qualquer assinatura)
                session(['referrer_slug' => $referrerSlug]);
                // Armazena também o creator_slug para redirecionar após cadastro
                session(['creator_slug' => $actualCreatorSlug]);
                
                // Captura a URL completa com query params para armazenar no cadastro
                $fullUrl = request()->fullUrl();
                session(['registration_url' => $fullUrl]);
                
                // Redireciona para a página de cadastro
                // Anexa cookies ao redirect para garantir que sejam enviados corretamente
                return redirect()
                    ->route('register')
                    ->with('info', 'Cadastre-se para acessar o perfil deste criador!')
                    ->cookie('referrer_slug', $referrerSlug, 60 * 24 * 30) // 30 dias
                    ->cookie('creator_slug', $actualCreatorSlug, 60 * 24 * 30) // 30 dias
                    ->cookie('registration_url', $fullUrl, 60 * 24 * 30); // 30 dias
            }
        }
        
        // Busca o usuário pelo slug (pode ser um criador ou um referrer)
        $user = User::where('slug', $actualCreatorSlug)->first();
        
        // Se não encontrou por slug, tenta buscar por username (compatibilidade)
        if (!$user) {
            $user = User::where('username', $actualCreatorSlug)->firstOrFail();
        }
        
        // Se usuário não autenticado acessa um perfil e esse perfil pode ser um link de indicação
        // Verifica se o slug corresponde a um usuário válido
        // Se o usuário não estiver autenticado e acessar um perfil, permite visualizar normalmente
        // Mas se for um link de indicação (apenas referrerSlug), deve redirecionar para cadastro
        // Isso é tratado acima quando há dois parâmetros
        
        $isOwner = Auth::check() && Auth::id() === $user->id;
        
        // Se o usuário autenticado está tentando acessar seu próprio perfil mas não tem username, redireciona
        if ($isOwner && !Auth::user()->username) {
            return redirect()->route('profile.edit')->with('info', 'Por favor, defina um username primeiro.');
        }
        
        // Busca planos de assinatura ativos
        $plans = SubscriptionPlan::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('duration_days')
            ->get();
        
        // Verifica se o usuário autenticado tem assinatura ativa com este criador
        $hasActiveSubscription = false;
        if (Auth::check() && !$isOwner) {
            $hasActiveSubscription = Auth::user()->hasActiveSubscription($user->id);
        }
        
        // Calcula estatísticas do criador
        $postsCount = \App\Models\Post::where('user_id', $user->id)->count();
        $videosCount = \App\Models\Post::where('user_id', $user->id)
            ->whereHas('media', function($q) {
                $q->where('file_type', 'video');
            })
            ->count();
        
        // Obtém Instagram do social_media
        $instagramUrl = null;
        if ($user->social_media && isset($user->social_media['instagram'])) {
            $instagramUrl = 'https://instagram.com/' . $user->social_media['instagram'];
        }
        $privateContentCount = \App\Models\Post::where('user_id', $user->id)
            ->where('visibility', 'subscriber')
            ->count();
        $totalLikes = \App\Models\PostLike::whereHas('post', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();
        
        // Prepara dados para o link de compartilhamento
        $shareLink = $this->generateShareLink($user, $referrerSlug);
        
        // Busca postagens do criador (apenas para usuários autenticados)
        if (Auth::check()) {
            $postsQuery = \App\Models\Post::where('user_id', $user->id)
                ->with(['user', 'media', 'likes', 'comments'])
                ->orderBy('created_at', 'desc');
            
            // Se não é o dono do perfil, busca todas as postagens
            // O componente post-card decide se deve mostrar bloqueado ou não
            if (!$isOwner) {
                // Busca todas as postagens (free e subscriber)
                // O post-card verifica a assinatura e mostra bloqueado se necessário
                $postsQuery->whereIn('visibility', ['free', 'subscriber', 'paid']);
            }
            
            $posts = $postsQuery->paginate(12);
        } else {
            // Para usuários não autenticados, não busca postagens
            // Cria um paginator vazio
            $posts = new LengthAwarePaginator(
                collect([]), // items
                0, // total
                12, // perPage
                1, // currentPage
                ['path' => request()->url(), 'query' => request()->query()] // options
            );
        }
        
        return view('profile.show', compact(
            'user', 
            'isOwner', 
            'plans', 
            'hasActiveSubscription', 
            'shareLink', 
            'referrerSlug',
            'postsCount',
            'videosCount',
            'privateContentCount',
            'totalLikes',
            'instagramUrl',
            'posts'
        ));
    }
    
    /**
     * Gera o link de compartilhamento baseado nas regras de negócio
     */
    private function generateShareLink(User $creator, $referrerSlug = null): string
    {
        $baseUrl = config('app.url');
        
        // Se o criador está acessando seu próprio perfil, link sem indicador
        if (Auth::check() && Auth::id() === $creator->id) {
            return $baseUrl . '/' . $creator->slug;
        }
        
        // Se o usuário autenticado está visitando (mas não é o criador)
        // O link deve conter dois slugs com prefixo /a/: /a/referrerSlug/creatorSlug
        // Isso permite redirecionar para o criador após cadastro, mas indicação vale para qualquer assinatura
        if (Auth::check() && Auth::id() !== $creator->id) {
            return $baseUrl . '/a/' . Auth::user()->slug . '/' . $creator->slug;
        }
        
        // Se há um referrer slug na URL (usuário não autenticado acessando via link de indicação)
        // Link de indicação com dois slugs e prefixo /a/: /a/referrerSlug/creatorSlug
        // Isso permite redirecionar para o criador após cadastro, mas indicação vale para qualquer assinatura
        if ($referrerSlug) {
            return $baseUrl . '/a/' . $referrerSlug . '/' . $creator->slug;
        }
        
        // Caso padrão (usuário não autenticado acessando diretamente): apenas o slug do criador
        return $baseUrl . '/' . $creator->slug;
    }

    /**
     * Mostra o formulário de edição do perfil
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Atualiza o perfil do usuário
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users')->ignore($user->id),
            ],
            'description' => 'nullable|string|max:2000',
            'cover_photo' => 'nullable|image|mimes:jpeg,jpg,png,heic,heif|max:307200', // 300MB
            'profile_photo' => 'nullable|image|mimes:jpeg,jpg,png,heic,heif|max:307200', // 300MB
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'youtube' => 'nullable|string|max:255',
        ]);

        // Upload da foto de capa localmente
        if ($request->hasFile('cover_photo')) {
            $uploadedFile = $this->savePhotoLocally($request->file('cover_photo'));
            
            if ($uploadedFile) {
                // Deleta a foto antiga se existir
                if ($user->cover_photo) {
                    $this->deletePhotoLocally($user->cover_photo);
                }
                $validated['cover_photo'] = $uploadedFile;
            } else {
                return back()->with('error', 'Erro ao fazer upload da foto de capa. Tente novamente.');
            }
        }

        // Upload da foto de perfil localmente
        if ($request->hasFile('profile_photo')) {
            $uploadedFile = $this->savePhotoLocally($request->file('profile_photo'));
            
            if ($uploadedFile) {
                // Deleta a foto antiga se existir
                if ($user->profile_photo) {
                    $this->deletePhotoLocally($user->profile_photo);
                }
                $validated['profile_photo'] = $uploadedFile;
            } else {
                return back()->with('error', 'Erro ao fazer upload da foto de perfil. Tente novamente.');
            }
        }

        // Prepara redes sociais
        $socialMedia = [];
        if (!empty($validated['instagram'])) {
            $socialMedia['instagram'] = $validated['instagram'];
        }
        if (!empty($validated['facebook'])) {
            $socialMedia['facebook'] = $validated['facebook'];
        }
        if (!empty($validated['twitter'])) {
            $socialMedia['twitter'] = $validated['twitter'];
        }
        if (!empty($validated['youtube'])) {
            $socialMedia['youtube'] = $validated['youtube'];
        }
        $validated['social_media'] = !empty($socialMedia) ? $socialMedia : null;

        // Remove campos de redes sociais do validated (já foram processados)
        unset($validated['instagram'], $validated['facebook'], $validated['twitter'], $validated['youtube']);

        $user->update($validated);

        return redirect()->route('profile.show', $user->username)
            ->with('success', 'Perfil atualizado com sucesso!');
    }

    /**
     * Atualiza apenas a foto de capa
     */
    public function updateCoverPhoto(Request $request)
    {
        $request->validate([
            'cover_photo' => 'required|image|mimes:jpeg,jpg,png,heic,heif|max:307200', // 300MB
        ]);

        $user = Auth::user();

        // Salva localmente
        $uploadedFile = $this->savePhotoLocally($request->file('cover_photo'));
        
        if (!$uploadedFile) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload da foto de capa.',
            ], 500);
        }

        // Deleta a foto antiga se existir
        if ($user->cover_photo) {
            $this->deletePhotoLocally($user->cover_photo);
        }

        $user->update(['cover_photo' => $uploadedFile]);

        return response()->json([
            'success' => true,
            'url' => $user->cover_photo_url,
        ]);
    }

    /**
     * Remove a foto de capa
     */
    public function deleteCoverPhoto()
    {
        $user = Auth::user();

        if ($user->cover_photo) {
            // Deleta o arquivo físico
            $this->deletePhotoLocally($user->cover_photo);
            // Remove do banco
            $user->update(['cover_photo' => null]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Busca planos de assinatura de um criador via AJAX
     */
    public function getCreatorPlans($userId)
    {
        $creator = User::findOrFail($userId);
        
        // Verifica se é um criador aprovado
        if ($creator->creator_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Este usuário não é um criador aprovado.',
            ], 404);
        }
        
        $plans = SubscriptionPlan::where('user_id', $creator->id)
            ->where('is_active', true)
            ->orderBy('duration_days')
            ->get(['id', 'name', 'price', 'duration_days']);
        
        return response()->json([
            'success' => true,
            'creator' => [
                'id' => $creator->id,
                'name' => $creator->name,
                'profile_photo' => $creator->profile_photo_url,
            ],
            'plans' => $plans,
        ]);
    }

    /**
     * Atualiza apenas a foto de perfil
     */
    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,jpg,png,heic,heif|max:307200', // 300MB
        ]);

        $user = Auth::user();

        // Salva localmente
        $uploadedFile = $this->savePhotoLocally($request->file('profile_photo'));
        
        if (!$uploadedFile) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload da foto de perfil.',
            ], 500);
        }

        // Deleta a foto antiga se existir
        if ($user->profile_photo) {
            $this->deletePhotoLocally($user->profile_photo);
        }

        $user->update(['profile_photo' => $uploadedFile]);

        return response()->json([
            'success' => true,
            'url' => $user->profile_photo_url,
        ]);
    }

    /**
     * Salva uma foto localmente na pasta public/_files_/
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string|null Retorna o nome do arquivo salvo ou null em caso de erro
     */
    private function savePhotoLocally($file)
    {
        try {
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $destinationPath = public_path('_files_');
            
            $file->move($destinationPath, $fileName);
            
            Log::info('Foto salva localmente', [
                'original_name' => $file->getClientOriginalName(),
                'saved_name' => $fileName
            ]);
            
            return $fileName;
        } catch (\Exception $e) {
            Log::error('Erro ao salvar foto localmente: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Deleta uma foto localmente da pasta public/_files_/
     * 
     * @param string $fileName
     * @return void
     */
    private function deletePhotoLocally($fileName)
    {
        try {
            $filePath = public_path('_files_/' . $fileName);
            
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info('Foto deletada', ['path' => $filePath]);
            } else {
                Log::warning('Foto não encontrada para deleção', ['path' => $filePath]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao deletar foto localmente: ' . $e->getMessage(), [
                'file' => $fileName,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
