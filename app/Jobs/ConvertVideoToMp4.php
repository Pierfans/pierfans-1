<?php

namespace App\Jobs;

use App\Models\PostMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConvertVideoToMp4 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 2;

    public function __construct(public int $mediaId) {}

    public function handle(): void
    {
        $media = PostMedia::find($this->mediaId);
        if (!$media) {
            Log::warning('ConvertVideoToMp4: media não encontrada', ['id' => $this->mediaId]);
            return;
        }

        $originalPath = $media->file_path;
        $ext = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));

        if (!in_array($ext, ['mov'])) {
            return;
        }

        Log::info('ConvertVideoToMp4: iniciando', ['mediaId' => $this->mediaId, 'path' => $originalPath]);

        $isR2 = Str::startsWith($originalPath, 'uploads/posts/');
        $tmpInput = tempnam(sys_get_temp_dir(), 'mov_') . '.mov';
        $tmpOutput = tempnam(sys_get_temp_dir(), 'mp4_') . '.mp4';

        try {
            // Obtém o arquivo de origem
            if ($isR2) {
                $contents = Storage::disk('r2')->get($originalPath);
                if (!$contents) {
                    Log::error('ConvertVideoToMp4: falha ao baixar do R2', ['path' => $originalPath]);
                    return;
                }
                file_put_contents($tmpInput, $contents);
            } else {
                $localPath = public_path('_files_/' . $originalPath);
                if (!file_exists($localPath)) {
                    Log::error('ConvertVideoToMp4: arquivo local não encontrado', ['path' => $localPath]);
                    return;
                }
                $tmpInput = $localPath;
            }

            // Converte com FFmpeg
            $cmd = "ffmpeg -i " . escapeshellarg($tmpInput) . " -c:v libx264 -c:a aac -movflags +faststart " . escapeshellarg($tmpOutput) . " -y 2>&1";
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                Log::error('ConvertVideoToMp4: ffmpeg falhou', ['returnCode' => $returnCode]);
                return;
            }

            $newPath = Str::replaceLast('.' . $ext, '.mp4', $originalPath);

            if ($isR2) {
                Storage::disk('r2')->put($newPath, file_get_contents($tmpOutput), 'private');
                Storage::disk('r2')->delete($originalPath);
            } else {
                $newLocalPath = public_path('_files_/' . $newPath);
                rename($tmpOutput, $newLocalPath);
                // Não deleta o original por segurança
            }

            $media->update(['file_path' => $newPath]);
            Log::info('ConvertVideoToMp4: concluído', ['mediaId' => $this->mediaId, 'newPath' => $newPath]);

        } finally {
            if ($isR2 && file_exists($tmpInput)) unlink($tmpInput);
            if (file_exists($tmpOutput)) unlink($tmpOutput);
        }
    }
}
