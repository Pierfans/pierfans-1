<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editar Perfil - {{ config('app.name', 'Laravel') }}</title>

    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/profile-overlay.css">
    <script src="/js/app.js"></script>
    <script src="/js/profile-overlay.js"></script>

    <style>
        .form-container {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            color: #1b1b18;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #1b1b18;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E5E5E5;
            border-radius: 8px;
            font-size: 16px;
            color: #1b1b18;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E5E5E5;
            border-radius: 8px;
            font-size: 16px;
            color: #1b1b18;
            min-height: 120px;
            resize: vertical;
            transition: border-color 0.2s;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }

        .btn-primary {
            background: #FF6B35;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: #e55a2b;
        }

        .btn-secondary {
            background: white;
            color: #1b1b18;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            border: 1px solid #E5E5E5;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: #F5F5F5;
        }

        .social-section {
            margin-top: 32px;
            padding-top: 32px;
            border-top: 1px solid #E5E5E5;
        }

        .social-title {
            font-size: 18px;
            font-weight: 600;
            color: #1b1b18;
            margin-bottom: 16px;
        }

        .social-input-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .social-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .social-input {
            flex: 1;
        }
    </style>
</head>
<body class="bg-[#F5F5F5] text-[#1b1b18] min-h-screen">
    <!-- Top Navigation -->
    <x-topnav />

    <!-- Bottom Navigation -->
    <x-bottomnav />

    <!-- Profile Drawer -->
    <x-profile-drawer />

    <!-- Main Content -->
    <div class="pt-0 md:pt-16 pb-16 md:pb-0">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="form-container">
                <h1 class="form-title">Editar Perfil</h1>

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" id="name" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-input" value="{{ old('username', $user->username) }}" required>
                        <p class="text-sm text-gray-500 mt-1">Seu perfil estará disponível em: /{{ old('username', $user->username ?? 'username') }}</p>
                        @error('username')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea id="description" name="description" class="form-textarea" maxlength="2000">{{ old('description', $user->description) }}</textarea>
                        <p class="text-sm text-gray-500 mt-1">Máximo de 2000 caracteres</p>
                        @error('description')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Redes Sociais -->
                    <div class="social-section">
                        <h2 class="social-title">Redes Sociais</h2>

                        <div class="social-input-group">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" fill="white"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke="white" stroke-width="2"></line>
                            </svg>
                            <input type="text" name="instagram" class="form-input social-input" placeholder="Instagram (sem @)" value="{{ old('instagram', $user->social_media['instagram'] ?? '') }}">
                        </div>

                        <div class="social-input-group">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                            <input type="text" name="facebook" class="form-input social-input" placeholder="Facebook" value="{{ old('facebook', $user->social_media['facebook'] ?? '') }}">
                        </div>

                        <div class="social-input-group">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>
                            </svg>
                            <input type="text" name="twitter" class="form-input social-input" placeholder="Twitter (sem @)" value="{{ old('twitter', $user->social_media['twitter'] ?? '') }}">
                        </div>

                        <div class="social-input-group">
                            <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                                <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="white"></polygon>
                            </svg>
                            <input type="text" name="youtube" class="form-input social-input" placeholder="YouTube" value="{{ old('youtube', $user->social_media['youtube'] ?? '') }}">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Salvar Alterações</button>
                        @if($user->username)
                            <a href="{{ route('profile.show', $user->username) }}" class="btn-secondary">Cancelar</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn-secondary">Cancelar</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Overlay -->
    <x-profile-overlay />
</body>
</html>

