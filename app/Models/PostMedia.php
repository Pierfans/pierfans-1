<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PostMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'file_path',
        'file_type',
        'order',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Relacionamento com Post
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna a URL para exibir o arquivo.
     * R2: rota que redireciona para presigned GET. Local: asset _files_.
     */
    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->file_path, 'uploads/posts/')) {
            return route('post-media.stream', $this->id);
        }
        return asset('_files_/' . $this->file_path);
    }

    /**
     * Boot: ao deletar o registro, remove o arquivo do storage (R2 ou local).
     */
    protected static function booted(): void
    {
        static::deleting(function (PostMedia $media) {
            $media->deleteFileFromStorage();
        });
    }

    /**
     * Remove o arquivo do storage (R2 ou disco local public/_files_).
     * Chamado automaticamente no evento deleting; pode ser usado fora do model.
     */
    public function deleteFileFromStorage(): void
    {
        $path = $this->file_path;
        if (str_starts_with($path, 'uploads/posts/')) {
            try {
                if (Storage::disk('r2')->exists($path)) {
                    Storage::disk('r2')->delete($path);
                    Log::info('Arquivo removido do R2', ['path' => $path]);
                }
            } catch (\Throwable $e) {
                Log::error('Erro ao deletar arquivo no R2: ' . $e->getMessage(), [
                    'path' => $path,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            return;
        }

        try {
            $fullPath = public_path('_files_/' . $path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
                Log::info('Arquivo removido do disco local', ['path' => $fullPath]);
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao deletar arquivo local: ' . $e->getMessage(), [
                'path' => $path,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
