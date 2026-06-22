<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminReportController extends Controller
{
    /**
     * Lista todas as denúncias
     */
    public function index(Request $request)
    {
        $query = Report::with(['post.user', 'user', 'reviewer'])
            ->orderBy('created_at', 'desc');

        // Filtro por status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(20)->appends($request->query());

        return view('admin.reports.index', compact('reports'));
    }

    /**
     * Mostra os detalhes de uma denúncia
     */
    public function show($id)
    {
        $report = Report::with(['post.user', 'post.media', 'user', 'reviewer'])
            ->findOrFail($id);

        return view('admin.reports.show', compact('report'));
    }

    /**
     * Aprova uma denúncia
     */
    public function approve(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        if ($report->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Esta denúncia já foi revisada.',
            ], 400);
        }

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
            'delete_post' => 'boolean',
        ]);

        $report->update([
            'status' => 'approved',
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // Se marcado para deletar, deleta a postagem
        if ($request->has('delete_post') && $request->delete_post) {
            $post = $report->post;
            
            // Deleta todas as mídias
            foreach ($post->media as $media) {
                Storage::disk('public')->delete($media->file_path);
                $media->delete();
            }
            
            // Deleta a postagem (cascade deleta reports, likes, comments)
            $post->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Denúncia aprovada com sucesso.',
        ]);
    }

    /**
     * Rejeita uma denúncia
     */
    public function reject(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        if ($report->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Esta denúncia já foi revisada.',
            ], 400);
        }

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $report->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Denúncia rejeitada.',
        ]);
    }
}
