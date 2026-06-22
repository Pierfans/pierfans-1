<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Cria uma denúncia de postagem
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_id' => 'required|exists:posts,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Verifica se o usuário já denunciou esta postagem
        $existingReport = Report::where('post_id', $validated['post_id'])
            ->where('user_id', Auth::id())
            ->first();

        if ($existingReport) {
            return response()->json([
                'success' => false,
                'message' => 'Você já denunciou esta postagem.',
            ], 400);
        }

        // Verifica se o usuário não está denunciando sua própria postagem
        $post = \App\Models\Post::findOrFail($validated['post_id']);
        if ($post->user_id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode denunciar sua própria postagem.',
            ], 400);
        }

        $report = Report::create([
            'post_id' => $validated['post_id'],
            'user_id' => Auth::id(),
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Denúncia enviada com sucesso. Nossa equipe irá analisar.',
        ]);
    }
}
