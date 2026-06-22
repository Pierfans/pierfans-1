<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    /**
     * Lista todas as postagens (feed)
     * Apenas postagens de criadores selecionados no admin aparecem
     */
    public function index()
    {
        $posts = Post::with(['user', 'media', 'likes', 'comments'])
            ->whereIn('visibility', ['free', 'paid'])
            ->where('featured_on_dashboard', true)
            ->whereHas('user', function ($q) {
                $q->where('creator_status', 'approved');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $featuredCreators = \App\Models\User::where('creator_status', 'approved')
            ->where('featured_in_top_creators', true)
            ->whereNotNull('username') // Apenas criadores com username (necessário para acessar o perfil)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard', compact('posts', 'featuredCreators'));
    }

    /**
     * Mostra o formulário de criação de postagem
     */
    public function create()
    {
        $user = Auth::user();
        
        // Verifica se é criador aprovado
        if ($user->creator_status !== 'approved') {
            return redirect()->route('dashboard')->with('error', 'Você precisa ser um criador aprovado para criar postagens.');
        }

        $useR2Upload = PlatformSetting::isUseR2Upload();
        $hasSubscriptionPlans = $user->hasActiveSubscriptionPlans();

        return view('posts.create', compact('useR2Upload', 'hasSubscriptionPlans'));
    }

    /**
     * Salva uma nova postagem
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Verifica se é criador aprovado
        if ($user->creator_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa ser um criador aprovado para criar postagens.',
            ], 403);
        }

        // Normaliza preço: troca vírgula por ponto (usuários brasileiros digitam 29,90)
        if ($request->has('price') && $request->price) {
            $request->merge(['price' => str_replace(',', '.', $request->price)]);
        }

        // Validação: aceita upload via chunks (uploaded_files), upload tradicional (media), ou sem mídia (R2 flow)
        $validated = $request->validate([
            'description' => 'nullable|string|max:5000',
            'visibility' => ['required', Rule::in(['free', 'subscriber', 'paid'])],
            'price'      => 'required_if:visibility,paid|nullable|numeric|min:1|max:9999',
            'media' => 'nullable|array',
            'media.*' => 'file|mimes:jpeg,jpg,png,heic,heif,mp4,mov,avi|max:307200', // Upload tradicional
            'uploaded_files' => 'nullable|array', // Arquivos via chunks (ou vazio para R2)
            'uploaded_files.*.filename' => 'required|string',
            'uploaded_files.*.file_type' => 'required|in:image,video',
        ]);

        if ($validated['visibility'] === 'subscriber' && ! $user->hasActiveSubscriptionPlans()) {
            return response()->json([
                'success' => false,
                'message' => 'É necessário ter pelo menos um plano de assinatura ativo para publicar como Somente assinantes.',
            ], 422);
        }

        // Cria o post
        $post = Post::create([
            'user_id'     => $user->id,
            'description' => $validated['description'],
            'visibility'  => $validated['visibility'],
            'price'       => $validated['visibility'] === 'paid' ? $validated['price'] : null,
        ]);

        $uploadErrors = [];

        // Processa arquivos enviados via chunks (novo método)
        // Permite uploaded_files vazio para fluxo R2: criar post primeiro, depois adicionar mídia via confirm-upload
        if ($request->has('uploaded_files') && is_array($request->uploaded_files)) {
            foreach ($request->uploaded_files as $uploadedFile) {
                try {
                    // Verifica se o arquivo existe em _files_
                    $filePath = public_path('_files_/' . $uploadedFile['filename']);
                    
                    if (!file_exists($filePath)) {
                        $uploadErrors[] = "Arquivo {$uploadedFile['original_name']} não encontrado";
                        continue;
                    }
                    
                    // Salva no banco
                    $media = PostMedia::create([
                        'post_id' => $post->id,
                        'file_path' => $uploadedFile['filename'],
                        'file_type' => $uploadedFile['file_type'],
                        'order' => $uploadedFile['order'] ?? 0,
                    ]);
                    
                    Log::info('Mídia (via chunks) salva no banco', [
                        'media_id' => $media->id,
                        'file_path' => $uploadedFile['filename'],
                        'file_type' => $uploadedFile['file_type']
                    ]);
                    // Dispara conversão automática se for .mov
                    $ext = strtolower(pathinfo($uploadedFile['filename'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['mov'])) {
                        \App\Jobs\ConvertVideoToMp4::dispatch($media->id)->delay(now()->addSeconds(5));
                        Log::info('ConvertVideoToMp4: job disparado (chunks)', ['mediaId' => $media->id]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao processar arquivo via chunks', [
                        'file' => $uploadedFile['original_name'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    $uploadErrors[] = "Erro ao processar {$uploadedFile['original_name']}: {$e->getMessage()}";
                }
            }
        }
        // Processa upload tradicional (fallback/compatibilidade)
        elseif ($request->hasFile('media')) {
            $order = 0;
            
            foreach ($request->file('media') as $file) {
                try {
                    // Determina o tipo de arquivo ANTES de mover
                    $fileType = $this->detectFileType($file);
                    
                    // Salva o arquivo localmente
                    $savedFile = $this->saveFileLocally($file);
                    
                    if (!$savedFile) {
                        $uploadErrors[] = "Erro ao fazer upload de {$file->getClientOriginalName()}";
                        continue;
                    }
                    
                    // Salva o nome do arquivo no banco
                    $media = PostMedia::create([
                        'post_id' => $post->id,
                        'file_path' => $savedFile,
                        'file_type' => $fileType,
                        'order' => $order++,
                    ]);
                    
                    Log::info('Mídia (upload tradicional) salva no banco', [
                        'media_id' => $media->id,
                        'file_path' => $savedFile,
                        'file_type' => $fileType
                    ]);
                    // Dispara conversão automática se for .mov
                    $ext = strtolower(pathinfo($savedFile, PATHINFO_EXTENSION));
                    if (in_array($ext, ['mov'])) {
                        \App\Jobs\ConvertVideoToMp4::dispatch($media->id)->delay(now()->addSeconds(5));
                        Log::info('ConvertVideoToMp4: job disparado (tradicional)', ['mediaId' => $media->id]);
                    }
                } catch (\Exception $e) {
                    // Se salvou o arquivo mas falhou ao criar no banco, remove o arquivo
                    if (isset($savedFile) && $savedFile) {
                        $this->deleteFileLocally($savedFile);
                    }
                    
                    Log::error('Erro ao processar arquivo', [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $uploadErrors[] = "Erro ao processar {$file->getClientOriginalName()}: {$e->getMessage()}";
                }
            }
        } elseif (!$request->boolean('create_without_media')) {
            // Nenhum arquivo enviado e não é fluxo "criar sem mídia" (R2)
            $post->delete();
            return response()->json([
                'success' => false,
                'message' => 'Nenhum arquivo foi enviado.',
            ], 400);
        }

        // Se houve erros e nenhum arquivo foi salvo com sucesso (exceto fluxo R2: create_without_media)
        $isR2EmptyFlow = $request->boolean('create_without_media');
        if (!empty($uploadErrors) && $post->media()->count() === 0 && !$isR2EmptyFlow) {
            // Nenhum arquivo foi salvo no banco, deletar o post
            Log::warning('Nenhum arquivo foi enviado com sucesso, deletando post', [
                'post_id' => $post->id,
                'errors' => $uploadErrors
            ]);
            $post->delete();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer upload dos arquivos.',
                'errors' => $uploadErrors,
            ], 500);
        }
        
        // Se houve alguns erros mas alguns arquivos foram salvos
        if (!empty($uploadErrors)) {
            Log::warning('Alguns arquivos falharam no upload', [
                'post_id' => $post->id,
                'media_saved' => $post->media()->count(),
                'errors' => $uploadErrors
            ]);
        }

        Log::info('Postagem criada com sucesso', [
            'post_id' => $post->id,
            'media_count' => $post->media()->count(),
            'warnings' => $uploadErrors
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Postagem criada com sucesso!',
            'post_id' => $post->id,
            'user_username' => $user->username,
            'warnings' => !empty($uploadErrors) ? $uploadErrors : null,
        ]);
    }

    /**
     * Mostra o formulário de edição de postagem
     */
    public function edit($id)
    {
        $post = Post::with(['media'])->findOrFail($id);
        $user = Auth::user();

        // Verifica se o usuário é o dono da postagem
        if ($post->user_id !== $user->id) {
            return redirect()->route('dashboard')->with('error', 'Você não tem permissão para editar esta postagem.');
        }

        $hasSubscriptionPlans = $user->hasActiveSubscriptionPlans();

        return view('posts.edit', compact('post', 'hasSubscriptionPlans'));
    }

    /**
     * Atualiza uma postagem
     */
    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $user = Auth::user();

        // Verifica se o usuário é o dono da postagem
        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para editar esta postagem.',
            ], 403);
        }

        // Normaliza preço: troca vírgula por ponto
        if ($request->has('price') && $request->price) {
            $request->merge(['price' => str_replace(',', '.', $request->price)]);
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:5000',
            'visibility' => ['required', Rule::in(['free', 'subscriber', 'paid'])],
            'price'      => 'required_if:visibility,paid|nullable|numeric|min:1|max:9999',
            'media' => 'nullable|array',
            'media.*' => 'file|mimes:jpeg,jpg,png,heic,heif,mp4,mov,avi|max:307200',
            'delete_media' => 'nullable|array',
            'delete_media.*' => 'exists:post_media,id',
            'uploaded_files' => 'nullable|array',
            'uploaded_files.*.filename' => 'required|string',
            'uploaded_files.*.file_type' => 'required|in:image,video',
        ]);

        if ($validated['visibility'] === 'subscriber' && ! $user->hasActiveSubscriptionPlans()) {
            return response()->json([
                'success' => false,
                'message' => 'É necessário ter pelo menos um plano de assinatura ativo para publicar como Somente assinantes.',
            ], 422);
        }

        // Conta quantas mídias vão permanecer
        $remainingMediaCount = $post->media()->count();
        if ($request->has('delete_media')) {
            $remainingMediaCount -= count($request->delete_media);
        }
        
        // Conta quantas novas mídias serão adicionadas
        $newMediaCount = 0;
        if ($request->hasFile('media')) {
            $newMediaCount = count($request->file('media'));
        } elseif ($request->has('uploaded_files')) {
            $newMediaCount = count($request->uploaded_files);
        }
        
        // Validação: deve ter pelo menos uma mídia após as alterações
        if ($remainingMediaCount + $newMediaCount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'A postagem deve ter pelo menos uma mídia.',
            ], 400);
        }

        // Atualiza descrição e visibilidade
        $post->update([
            'description' => $validated['description'] ?? null,
            'visibility'  => $validated['visibility'],
            'price'       => $validated['visibility'] === 'paid' ? $validated['price'] : null,
        ]);

        // Deleta mídias selecionadas
        if ($request->has('delete_media')) {
            foreach ($request->delete_media as $mediaId) {
                $media = PostMedia::find($mediaId);
                if ($media && $media->post_id === $post->id) {
                    $media->delete(); // evento do model remove do storage (R2 ou local)
                }
            }
        }

        // Adiciona novas mídias
        $uploadErrors = [];
        $maxOrder = $post->media()->max('order') ?? -1;

        // Processa arquivos via chunks
        if ($request->has('uploaded_files') && is_array($request->uploaded_files)) {
            foreach ($request->uploaded_files as $uploadedFile) {
                try {
                    $filePath = public_path('_files_/' . $uploadedFile['filename']);
                    
                    if (!file_exists($filePath)) {
                        $uploadErrors[] = "Arquivo {$uploadedFile['original_name']} não encontrado";
                        continue;
                    }
                    
                    PostMedia::create([
                        'post_id' => $post->id,
                        'file_path' => $uploadedFile['filename'],
                        'file_type' => $uploadedFile['file_type'],
                        'order' => ++$maxOrder,
                    ]);
                    
                    Log::info('Mídia (via chunks) adicionada na edição', [
                        'post_id' => $post->id,
                        'filename' => $uploadedFile['filename']
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erro ao adicionar mídia via chunks na edição', [
                        'file' => $uploadedFile['original_name'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    $uploadErrors[] = "Erro ao processar {$uploadedFile['original_name']}: {$e->getMessage()}";
                }
            }
        }
        // Processa upload tradicional (fallback)
        elseif ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                try {
                    $fileType = $this->detectFileType($file);
                    $savedFile = $this->saveFileLocally($file);
                    
                    if (!$savedFile) {
                        $uploadErrors[] = "Erro ao fazer upload de {$file->getClientOriginalName()}";
                        continue;
                    }
                    
                    PostMedia::create([
                        'post_id' => $post->id,
                        'file_path' => $savedFile,
                        'file_type' => $fileType,
                        'order' => ++$maxOrder,
                    ]);
                } catch (\Exception $e) {
                    $uploadErrors[] = "Erro ao processar {$file->getClientOriginalName()}: {$e->getMessage()}";
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Postagem atualizada com sucesso!',
            'user_username' => $user->username,
            'warnings' => !empty($uploadErrors) ? $uploadErrors : null,
        ]);
    }

    /**
     * Marca postagem como deletada pelo usuário (soft delete)
     */
    public function destroy($id)
    {
        $post = Post::withoutGlobalScope('notDeletedByUser')->findOrFail($id);
        $user = Auth::user();

        // Verifica se o usuário é o dono da postagem
        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para deletar esta postagem.',
            ], 403);
        }

        // Marca como deletada pelo usuário ao invés de deletar fisicamente
        $post->update(['deleted_by_user_at' => now()]);

        Log::info('Postagem marcada como deletada pelo usuário', [
            'post_id' => $post->id,
            'user_id' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Postagem movida para a lixeira!',
        ]);
    }

    /**
     * Processa upload de chunks (partes) de arquivos grandes
     */
    public function uploadChunk(Request $request)
    {
        try {
            // Parâmetros do Resumable.js
            $identifier = $request->input('resumableIdentifier');
            $filename = $request->input('resumableFilename');
            $chunkNumber = $request->input('resumableChunkNumber');
            $totalChunks = $request->input('resumableTotalChunks');
            $chunkSize = $request->input('resumableChunkSize');
            $totalSize = $request->input('resumableTotalSize');
            
            // Validações básicas
            if (!$identifier || !$filename || !$chunkNumber || !$totalChunks) {
                return response()->json(['error' => 'Parâmetros inválidos'], 400);
            }
            
            // Pasta temporária para este arquivo
            $tempPath = storage_path('app/chunks/' . $identifier);
            
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0777, true);
            }
            
            // Salva o chunk atual
            $chunkPath = $tempPath . '/chunk_' . $chunkNumber;
            
            if ($request->hasFile('file')) {
                $request->file('file')->move($tempPath, 'chunk_' . $chunkNumber);
            } else {
                return response()->json(['error' => 'Chunk não encontrado'], 400);
            }
            
            // Verifica se todos os chunks foram recebidos
            $uploadedChunks = glob($tempPath . '/chunk_*');
            
            Log::info('Chunk recebido', [
                'identifier' => $identifier,
                'chunk' => $chunkNumber,
                'total' => $totalChunks,
                'received' => count($uploadedChunks)
            ]);
            
            // Se todos os chunks foram recebidos, junta o arquivo
            if (count($uploadedChunks) == $totalChunks) {
                Log::info('Todos os chunks recebidos, juntando arquivo', [
                    'identifier' => $identifier,
                    'filename' => $filename
                ]);
                
                // Gera nome único para o arquivo final
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $finalFilename = uniqid() . '_' . time() . '.' . $extension;
                $finalPath = public_path('_files_/' . $finalFilename);
                
                // Abre arquivo final para escrita
                $finalFile = fopen($finalPath, 'wb');
                
                if (!$finalFile) {
                    throw new \Exception('Não foi possível criar arquivo final');
                }
                
                // Junta todos os chunks na ordem correta
                for ($i = 1; $i <= $totalChunks; $i++) {
                    $chunkFile = fopen($tempPath . '/chunk_' . $i, 'rb');
                    
                    if (!$chunkFile) {
                        fclose($finalFile);
                        unlink($finalPath);
                        throw new \Exception('Chunk ' . $i . ' não encontrado');
                    }
                    
                    // Copia o chunk para o arquivo final
                    stream_copy_to_stream($chunkFile, $finalFile);
                    fclose($chunkFile);
                }
                
                fclose($finalFile);
                
                // Limpa os chunks temporários
                foreach ($uploadedChunks as $chunk) {
                    unlink($chunk);
                }
                rmdir($tempPath);
                
                // Detecta o tipo de arquivo
                $mimeType = mime_content_type($finalPath);
                $fileType = str_starts_with($mimeType, 'image/') ? 'image' : 'video';
                
                // Se não conseguiu detectar pelo mime, usa extensão
                if (!$mimeType || $mimeType === 'application/octet-stream') {
                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'];
                    $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm'];
                    
                    if (in_array(strtolower($extension), $imageExtensions)) {
                        $fileType = 'image';
                    } elseif (in_array(strtolower($extension), $videoExtensions)) {
                        $fileType = 'video';
                    }
                }
                
                Log::info('Arquivo completo criado', [
                    'filename' => $finalFilename,
                    'type' => $fileType,
                    'size' => filesize($finalPath)
                ]);
                
                return response()->json([
                    'success' => true,
                    'filename' => $finalFilename,
                    'file_type' => $fileType,
                    'original_name' => $filename,
                    'size' => filesize($finalPath)
                ]);
            }
            
            // Chunk salvo, aguardando próximos
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar chunk', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erro ao processar chunk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detecta o tipo de arquivo (imagem ou vídeo)
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string 'image' ou 'video'
     */
    private function detectFileType($file)
    {
        // Primeiro tenta pelo MIME type
        $mimeType = $file->getMimeType();
        
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        
        // Fallback: verifica pela extensão do arquivo
        $extension = strtolower($file->getClientOriginalExtension());
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm'];
        
        if (in_array($extension, $imageExtensions)) {
            return 'image';
        }
        
        if (in_array($extension, $videoExtensions)) {
            return 'video';
        }
        
        // Default para imagem se não conseguir detectar
        return 'image';
    }

    /**
     * Salva um arquivo localmente na pasta public/_files_
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string|null Retorna o nome do arquivo salvo ou null em caso de erro
     */
    private function saveFileLocally($file)
    {
        try {
            // Gera um nome único para o arquivo
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            
            // Define o caminho de destino
            $destinationPath = public_path('_files_');
            
            // Move o arquivo para a pasta
            $file->move($destinationPath, $fileName);
            
            Log::info('Arquivo salvo com sucesso', [
                'original_name' => $file->getClientOriginalName(),
                'saved_name' => $fileName,
                'path' => $destinationPath
            ]);
            
            return $fileName;
        } catch (\Exception $e) {
            Log::error('Erro ao salvar arquivo localmente: ' . $e->getMessage(), [
                'original_name' => $file->getClientOriginalName(),
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Deleta um arquivo local da pasta public/_files_
     * 
     * @param string $fileName
     * @return void
     */
    private function deleteFileLocally($fileName)
    {
        try {
            $filePath = public_path('_files_/' . $fileName);
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao deletar arquivo localmente: ' . $e->getMessage());
        }
    }
}
