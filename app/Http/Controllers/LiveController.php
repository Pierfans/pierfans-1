<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;

class LiveController extends Controller
{
    /**
     * Pagina publica da live (banner + player).
     * O link vem de admin > configuracoes da plataforma, campo 'live_url'.
     * Vazio = estado "em breve", sem player.
     */
    public function show()
    {
        $liveUrl = PlatformSetting::getValue('live_url');

        return view('live', [
            'liveUrl'  => $liveUrl,
            'embedUrl' => self::embedUrl($liveUrl),
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
