<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serve documento de identidade de criador (frente, verso, selfie).
 *
 * Antes esses arquivos ficavam em `public/_files_/documents/` e eram baixáveis por QUALQUER
 * pessoa com a URL — sem login. Pior: o nome era `uniqid() . '_' . time()`, os dois derivados
 * do relógio, então nem "URL secreta" era. Agora vivem fora do public/ e só saem por aqui,
 * com dono ou admin autenticado.
 */
class CreatorDocumentController extends Controller
{
    /** Pasta privada, fora do public/. */
    public const DIR = 'creator-documents';

    private const CAMPOS = [
        'frente' => 'creator_document_front',
        'verso'  => 'creator_document_back',
        'selfie' => 'creator_selfie',
    ];

    public function show(int $userId, string $tipo): BinaryFileResponse
    {
        $campo = self::CAMPOS[$tipo] ?? abort(404);

        $atual = Auth::user();
        // Dono vê o próprio documento; admin vê o de qualquer um. Mais ninguém.
        abort_unless($atual && ($atual->id === $userId || $atual->is_admin), 404);

        $dono = User::withoutGlobalScope('active')->find($userId) ?? abort(404);

        $arquivo = $dono->{$campo};
        abort_unless($arquivo, 404);

        // basename(): o nome vem do banco, mas nunca deixar '../' virar leitura de arquivo do servidor.
        $caminho = storage_path('app/' . self::DIR . '/' . basename($arquivo));
        abort_unless(is_file($caminho), 404);

        // inline pra abrir no navegador (o admin exibe em <img>); noindex por garantia.
        $resposta = response()->file($caminho, ['X-Robots-Tag' => 'noindex, nofollow']);

        // Sobrescreve DEPOIS: o response()->file() marca a resposta como 'public' sozinho, e um
        // CDN na frente (temos Cloudflare) guardaria documento de identidade no cache de borda —
        // onde continua servindo mesmo depois do arquivo sair do servidor. Já aconteceu aqui.
        $resposta->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $resposta;
    }
}
