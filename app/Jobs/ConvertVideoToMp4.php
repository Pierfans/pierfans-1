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

    public int $timeout = 3600;
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
        $ext = pathinfo($originalPath, PATHINFO_EXTENSION);

        if (strtolower($ext) !== 'mov') {
            return;
        }

        // Extensao vem como veio do celular (.MOV do iPhone) - cortar pelo tamanho,
        // nao por replace de string, senao o path novo sai igual ao antigo e no R2
        // o put+delete apaga o arquivo que acabou de ser gravado.
        $newPath = substr($originalPath, 0, -strlen($ext)) . 'mp4';
        if ($newPath === $originalPath) {
            Log::error('ConvertVideoToMp4: novo path igual ao original, abortando', ['path' => $originalPath]);
            return;
        }

        Log::info('ConvertVideoToMp4: iniciando', ['mediaId' => $this->mediaId, 'path' => $originalPath]);

        $isR2 = Str::startsWith($originalPath, 'uploads/posts/');
        $tmpInput = tempnam(sys_get_temp_dir(), 'mov_') . '.mov';
        $tmpOutput = tempnam(sys_get_temp_dir(), 'mp4_') . '.mp4';

        try {
            // Obtém o arquivo de origem
            if ($isR2) {
                // stream, nao ->get(): um video de 300 MB viraria uma string de 300 MB
                // na memoria do PHP e estouraria o memory_limit
                $remoto = Storage::disk('r2')->readStream($originalPath);
                if (!$remoto) {
                    Log::error('ConvertVideoToMp4: falha ao baixar do R2', ['path' => $originalPath]);
                    return;
                }
                $local = fopen($tmpInput, 'wb');
                stream_copy_to_stream($remoto, $local);
                fclose($local);
                fclose($remoto);
            } else {
                $localPath = public_path('_files_/' . $originalPath);
                if (!file_exists($localPath)) {
                    Log::error('ConvertVideoToMp4: arquivo local não encontrado', ['path' => $localPath]);
                    return;
                }
                $tmpInput = $localPath;
            }

            // .mov de iPhone costuma ser HEVC (nao toca em Chrome/Windows/Android) mas
            // tambem existe .mov h264, que so precisa trocar de container. Recodificar
            // esse a toa gasta minutos de CPU e perde qualidade.
            exec("ffprobe -v error -select_streams v:0 -show_entries stream=codec_name -of csv=p=0 "
                . escapeshellarg($tmpInput) . " 2>&1", $probe);
            $codec = strtolower(trim($probe[0] ?? ''));

            $videoArgs = $codec === 'h264'
                ? '-c:v copy -c:a aac'
                // 16.8 Mbps de iPhone nao serve pra streaming; veryfast pra caber no timeout
                : '-c:v libx264 -preset veryfast -crf 26 -maxrate 6M -bufsize 12M -pix_fmt yuv420p -c:a aac -b:a 128k';

            $cmd = "ffmpeg -i " . escapeshellarg($tmpInput) . " {$videoArgs} -movflags +faststart "
                . escapeshellarg($tmpOutput) . " -y 2>&1";
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                Log::error('ConvertVideoToMp4: ffmpeg falhou', [
                    'returnCode' => $returnCode,
                    'codec' => $codec,
                    'saida' => implode("\n", array_slice($output, -10)),
                ]);
                return;
            }

            if ($isR2) {
                // sem ContentType o R2 grava como octet-stream e o navegador nao toca o video
                $saida = fopen($tmpOutput, 'rb');
                Storage::disk('r2')->writeStream($newPath, $saida, [
                    'visibility' => 'private',
                    'ContentType' => 'video/mp4',
                ]);
                fclose($saida);
                Storage::disk('r2')->delete($originalPath);
            } else {
                $newLocalPath = public_path('_files_/' . $newPath);
                rename($tmpOutput, $newLocalPath);
                // tempnam() cria 0600: sem isso o nginx nao le o arquivo e o video da 403
                chmod($newLocalPath, 0644);
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
