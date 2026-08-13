<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;

class LiveController extends Controller
{
    /**
     * Pagina publica da live (banner + player).
     * Os links vem de admin > configuracoes da plataforma. Sao dois, e a ordem
     * de preferencia esta na view:
     *
     *   live_stream_url (.m3u8) - toca no player da CASA, via hls.js. Sem botao
     *                             de terceiro puxando o visitante pra fora e com
     *                             controle do som. E o caminho preferido.
     *   live_url                - pagina da live do fornecedor. Vira iframe quando
     *                             nao ha stream, e e o fallback de quem o token do
     *                             .m3u8 vence no meio da transmissao. Tambem e o
     *                             destino do "abrir em nova aba" - por isso NAO
     *                             pode receber o .m3u8, que o navegador baixa
     *                             como arquivo em vez de tocar.
     *
     * Os dois vazios = estado "em breve", sem player.
     */
    public function show()
    {
        $liveUrl = PlatformSetting::getValue('live_url');

        return view('live', [
            'liveUrl'   => $liveUrl,
            'embedUrl'  => self::embedUrl($liveUrl),
            'streamUrl' => PlatformSetting::getValue('live_stream_url'),
        ]);
    }

    /**
     * Converte link de YouTube para o formato que aceita iframe.
     *
     * Conferido nos headers em 10/08/2026: youtube.com/watch?v= e youtu.be
     * respondem 'X-Frame-Options: SAMEORIGIN', ou seja, o browser RECUSA
     * desenhar o quadro. So /embed/ vem sem o header. Como o link que qualquer
     * pessoa copia da barra do navegador e o /watch, sem esta conversao o
     * frame fica em branco e ninguem entende por que.
     *
     * Qualquer outra URL passa intacta - se a plataforma nao aceitar iframe
     * (instagram responde 'DENY', tiktok 'SAMEORIGIN'), quem salva a visita
     * e o botao "abrir em nova aba" que a view mostra embaixo do player.
     */
    public static function embedUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $formatos = [
            '~^https?://(?:www\.)?youtu\.be/([\w-]{6,})~i',                    // youtu.be/ID
            '~^https?://(?:www\.)?youtube\.com/watch\?(?:.*&)?v=([\w-]{6,})~i', // youtube.com/watch?v=ID
            '~^https?://(?:www\.)?youtube\.com/live/([\w-]{6,})~i',            // youtube.com/live/ID (formato de live)
        ];

        foreach ($formatos as $regex) {
            if (preg_match($regex, $url, $m)) {
                return 'https://www.youtube.com/embed/' . $m[1];
            }
        }

        return $url;
    }
}
