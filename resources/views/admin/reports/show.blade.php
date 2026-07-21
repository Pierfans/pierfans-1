@extends('layouts.admin')

@section('title', 'Detalhes da Denúncia')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('admin.reports.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                ← Voltar
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Detalhes da Denúncia</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 space-y-6">
            <!-- Informações da Denúncia -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4">Informações da Denúncia</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <p class="mt-1">
                            @if($report->status === 'pending')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pendente
                                </span>
                            @elseif($report->status === 'approved')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Aprovada
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Rejeitada
                                </span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data da Denúncia</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $report->created_at->emBrasilia()->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Denunciado por</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $report->user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $report->user->email }}</p>
                    </div>
                    @if($report->reviewed_by)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Revisado por</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $report->reviewer->name }}</p>
                            <p class="text-sm text-gray-500">{{ $report->reviewed_at->emBrasilia()->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                </div>
                @if($report->reason)
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Motivo da Denúncia</label>
                        <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">{{ $report->reason }}</p>
                    </div>
                @endif
                @if($report->admin_notes)
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Observações do Admin</label>
                        <p class="mt-1 text-sm text-gray-900 bg-blue-50 p-3 rounded">{{ $report->admin_notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Postagem Denunciada -->
            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Postagem Denunciada</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                            @if($report->post->user->profile_photo)
                                <img src="{{ $report->post->user->profile_photo_url }}" alt="{{ $report->post->user->name }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-gray-600 font-medium text-sm">
                                    {{ strtoupper(substr($report->post->user->name, 0, 2)) }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">{{ $report->post->user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $report->post->created_at->emBrasilia()->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                    
                    @if($report->post->description)
                        <p class="text-gray-900 mb-4">{{ $report->post->description }}</p>
                    @endif

                    @if($report->post->media->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach($report->post->media->take(6) as $media)
                                @if($media->file_type === 'image')
                                    <img src="{{ $media->url }}" alt="Mídia" class="w-full h-32 object-cover rounded">
                                @else
                                    <div class="w-full h-32 bg-gray-200 rounded flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                                        </svg>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Ações -->
            @if($report->status === 'pending')
                <div class="border-t border-gray-200 pt-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Ações</h2>
                    
                    <!-- Formulário de Aprovar -->
                    <div class="mb-6 p-4 bg-red-50 rounded-lg">
                        <h3 class="font-semibold text-red-900 mb-3">Aprovar Denúncia</h3>
                        <form id="approveForm" onsubmit="approveReport(event, {{ $report->id }})">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações (opcional)</label>
                                <textarea 
                                    name="admin_notes" 
                                    rows="3" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                    placeholder="Adicione observações sobre a aprovação..."
                                ></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="flex items-center space-x-2">
                                    <input 
                                        type="checkbox" 
                                        name="delete_post" 
                                        value="1"
                                        class="w-4 h-4 text-red-600 rounded focus:ring-red-500"
                                    >
                                    <span class="text-sm font-medium text-gray-700">Deletar postagem</span>
                                </label>
                            </div>
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                Aprovar Denúncia
                            </button>
                        </form>
                    </div>

                    <!-- Formulário de Rejeitar -->
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-3">Rejeitar Denúncia</h3>
                        <form id="rejectForm" onsubmit="rejectReport(event, {{ $report->id }})">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações (opcional)</label>
                                <textarea 
                                    name="admin_notes" 
                                    rows="3" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500"
                                    placeholder="Adicione observações sobre a rejeição..."
                                ></textarea>
                            </div>
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                Rejeitar Denúncia
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function approveReport(e, reportId) {
            e.preventDefault();
            
            if (!confirm('Tem certeza que deseja aprovar esta denúncia?')) {
                return;
            }

            const formData = new FormData(e.target);
            const data = {
                admin_notes: formData.get('admin_notes') || null,
                delete_post: formData.get('delete_post') === '1'
            };

            fetch(`/admin/reports/${reportId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Denúncia aprovada com sucesso!');
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao aprovar denúncia');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao aprovar denúncia');
            });
        }

        function rejectReport(e, reportId) {
            e.preventDefault();
            
            if (!confirm('Tem certeza que deseja rejeitar esta denúncia?')) {
                return;
            }

            const formData = new FormData(e.target);
            const data = {
                admin_notes: formData.get('admin_notes') || null
            };

            fetch(`/admin/reports/${reportId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Denúncia rejeitada!');
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao rejeitar denúncia');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao rejeitar denúncia');
            });
        }
    </script>
@endsection

