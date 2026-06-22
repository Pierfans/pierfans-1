@props(['post'])

@php
    use Illuminate\Support\Facades\Storage;
    $mediaItems = $post->media;
    $firstMedia = $mediaItems->first();
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
    <!-- Header do Post -->
    <div class="flex items-center space-x-3 p-4 border-b border-gray-200">
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center overflow-hidden flex-shrink-0">
            @if ($post->user->profile_photo)
                <img src="{{ $post->user->profile_photo_url }}" alt="{{ $post->user->name }}"
                    class="w-full h-full object-cover">
            @else
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <div class="font-semibold text-gray-900 truncate">{{ $post->user->name }}</div>
            <span class="text-xs text-gray-500">{{ $post->created_at->format('d/m/Y') }}</span>
        </div>
    </div>

    <!-- Descrição -->
    @if ($post->description)
        <div class="px-4 pt-3 pb-2">
            <p class="text-sm text-gray-900 line-clamp-3">{{ $post->description }}</p>
        </div>
    @endif

    <!-- Mídia -->
    @if ($firstMedia)
        <div class="relative w-full">
            @if ($firstMedia->file_type === 'image')
                <img src="{{ $firstMedia->url }}" 
                     alt="Post media"
                     class="w-full h-auto object-cover max-h-96">
            @else
                <div class="relative w-full bg-gray-900" style="padding-bottom: 56.25%;">
                    <video 
                        src="{{ $firstMedia->url }}"
                        class="absolute top-0 left-0 w-full h-full object-cover"
                        controls
                        preload="metadata">
                    </video>
                </div>
            @endif
        </div>
    @endif
</div>





