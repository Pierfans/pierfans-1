@props(['media', 'post', 'isLocked' => false])

@php
    use Illuminate\Support\Facades\Storage;
    $isVideo = $media->file_type === 'video';
    $mediaUrl = Storage::url($media->file_path);
    $playerId = 'plyr-player-' . $media->id;
@endphp

<div class="media-player-wrapper" data-media-type="{{ $isVideo ? 'video' : 'image' }}" data-media-id="{{ $media->id }}">
    @if($isVideo)
        <!-- Video Player com Plyr -->
        <div class="plyr-video-container {{ $isLocked ? 'locked' : '' }}" data-player-id="{{ $playerId }}" data-locked="{{ $isLocked ? 'true' : 'false' }}">
            <video
                id="{{ $playerId }}"
                class="plyr-video"
                preload="metadata"
                playsinline
                data-poster=""
                @if($isLocked)
                    style="pointer-events: none;"
                @endif
            >
                <source src="{{ $mediaUrl }}" type="video/mp4">
            </video>

            <!-- Paywall Overlay -->
            @if($isLocked)
                <div class="media-paywall-overlay">
                    <div class="paywall-content">
                        <div class="paywall-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="paywall-title">Conteúdo Exclusivo</h3>
                        <p class="paywall-text">Assine para desbloquear</p>
                        <button class="paywall-button" onclick="unlockContent({{ $post->id }})">
                            Desbloquear
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @else
        <!-- Image Viewer -->
        <div class="plyr-image-container {{ $isLocked ? 'locked' : '' }}">
            <img
                src="{{ $mediaUrl }}"
                alt="Post image"
                class="plyr-image"
                loading="lazy"
                data-media-id="{{ $media->id }}"
            >

            <!-- Paywall Overlay -->
            @if($isLocked)
                <div class="media-paywall-overlay">
                    <div class="paywall-content">
                        <div class="paywall-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="paywall-title">Conteúdo Exclusivo</h3>
                        <p class="paywall-text">Assine para desbloquear</p>
                        <button class="paywall-button" onclick="unlockContent({{ $post->id }})">
                            Desbloquear
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
