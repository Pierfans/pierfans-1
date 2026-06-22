<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Models\PlatformSetting;
use App\Models\Post;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Mostra o formulário de login
     */
    public function showLoginForm()
    {
        $featuredPosts = Post::with(['user', 'media'])
            ->where('visibility', 'free')
            ->where('featured_on_login', true)
            ->whereHas('user', function ($q) {
                $q->where('creator_status', 'approved');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('auth.login', compact('featuredPosts'));
    }

    /**
     * Processa o login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'email' => 'Sua conta foi desativada. Entre em contato com o suporte.',
                ]);
            }

            // Criadora em onboarding: libera acesso direto ao /creator sem verificação de e-mail
            if ($user->creator_onboarding) {
                session()->forget('pending_verification_email');
                $request->session()->regenerate();
                return redirect()->route('creator.index');
            }

            // Verifica se a confirmação de e-mail é obrigatória e se o usuário não confirmou
            if (PlatformSetting::isEmailVerificationRequired() && !$user->email_verified_at) {
                // Armazena o e-mail na sessão para permitir reenvio
                session(['pending_verification_email' => $user->email]);

                Auth::logout();
                $request->session()->regenerateToken();

                return redirect()->route('verification.notice')
                    ->with('error', 'Por favor, confirme seu e-mail antes de fazer login.');
            }

            // Limpa a sessão de verificação pendente se existir
            session()->forget('pending_verification_email');

            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->withInput($request->only('email'));
    }

    /**
     * Mostra o formulário de cadastro
     */
    public function showRegisterForm(Request $request)
    {
        // Se não há registration_url já capturada, captura agora (caso acesso direto à página de registro)
        // Isso garante que mesmo acessos diretos ao /register sejam rastreados
        if (!session()->has('registration_url') && !$request->cookie('registration_url')) {
            $fullUrl = $request->fullUrl();
            session(['registration_url' => $fullUrl]);
        }
        
        return view('auth.register');
    }

    /**
     * Processa o cadastro
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $isCreator = $request->input('type') === 'creator';

        // Gera um slug único de até 10 caracteres
        $slug = $this->generateUniqueSlug();

        // Log inicial para debug
        \Log::info('Iniciando processo de registro', [
            'email' => $request->email,
            'registration_url_session' => session('registration_url'),
            'registration_url_cookie' => request()->cookie('registration_url'),
        ]);

        DB::beginTransaction();
        try {
            // Captura a URL completa de registro (se houver na sessão ou cookie)
            // Isso inclui query params (UTM, gclid, fbclid, etc.) do primeiro acesso
            // Prioriza sessão sobre cookie (mais confiável)
            $registrationUrl = session('registration_url');
            if (!$registrationUrl) {
                $registrationUrl = request()->cookie('registration_url');
            }

            \Log::info('Dados antes de criar usuário', [
                'name' => $request->name,
                'email' => $request->email,
                'slug' => $slug,
                'registration_url' => $registrationUrl,
            ]);

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'slug' => $slug,
                // Criadoras: email null (e-mail enviado mas não bloqueia o fluxo)
                // Usuárias normais: null se verificação obrigatória, senão now()
                'email_verified_at' => (!$isCreator && !PlatformSetting::isEmailVerificationRequired()) ? now() : null,
            ];

            // Adiciona registration_url apenas se existir
            if ($registrationUrl) {
                $userData['registration_url'] = $registrationUrl;
            }

            $user = User::create($userData);

            \Log::info('Usuário criado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Captura indicação se houver referrer slug na sessão
            $this->captureReferral($user);

            DB::commit();

            \Log::info('Transação commitada com sucesso', [
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log do erro completo para debug
            \Log::error('Erro ao criar usuário', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => [
                    'name' => $request->name,
                    'email' => $request->email,
                ],
            ]);

            // Retorna erro para o usuário
            return back()
                ->withErrors(['email' => 'Erro ao criar conta: ' . $e->getMessage()])
                ->withInput($request->only('name', 'email'));
        }

        // Fluxo especial para criadoras: login imediato sem bloqueio por e-mail
        if ($isCreator) {
            try {
                $this->sendVerificationEmail($user);
                \Log::info('E-mail de verificação enviado para criadora', ['user_id' => $user->id]);
            } catch (\Exception $e) {
                \Log::error('Erro ao enviar e-mail de verificação para criadora', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $user->creator_onboarding = true;
            $user->save();

            Auth::login($user);
            $this->clearReferralCookies();

            return redirect()->route('creator.index')
                ->with('success', 'Conta criada! Complete seu cadastro de criadora.');
        }

        // Fluxo normal: se confirmação de e-mail é obrigatória, bloqueia até verificar
        if (PlatformSetting::isEmailVerificationRequired()) {
            try {
                $this->sendVerificationEmail($user);
                \Log::info('E-mail de verificação enviado', ['user_id' => $user->id]);
            } catch (\Exception $e) {
                \Log::error('Erro ao enviar e-mail de verificação', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                // Continua mesmo se falhar o envio do e-mail
            }

            // Limpa cookies após criar a conta (indicação já foi capturada)
            $this->clearReferralCookies();

            // Armazena o e-mail na sessão para permitir reenvio sem autenticação
            session(['pending_verification_email' => $user->email]);

            // Verifica se o usuário realmente existe no banco antes de redirecionar
            $userExists = User::find($user->id);
            if (!$userExists) {
                \Log::error('Usuário não encontrado após criação', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
                return back()
                    ->withErrors(['email' => 'Erro ao criar conta. Por favor, tente novamente.'])
                    ->withInput($request->only('name', 'email'));
            }

            \Log::info('Redirecionando para verificação de e-mail', ['user_id' => $user->id]);

            // NÃO faz login - usuário precisa confirmar e-mail primeiro
            return redirect()->route('verification.notice')
                ->with('status', 'Conta criada com sucesso! Por favor, verifique seu e-mail para confirmar sua conta.');
        }

        // Se confirmação não é obrigatória, faz login normalmente
        Auth::login($user);

        // Verifica se há creator_slug na sessão ou cookies para redirecionar após cadastro
        // (apenas para redirecionamento, não afeta a indicação que vale para qualquer assinatura)
        $creatorSlug = session('creator_slug') ?? request()->cookie('creator_slug');

        // Limpa cookies após criar a conta (indicação já foi capturada)
        $this->clearReferralCookies();

        // Se havia creator_slug, redireciona para o perfil do criador
        if ($creatorSlug) {
            $creator = User::where('slug', $creatorSlug)->first();
            if ($creator) {
                return redirect()->route('profile.show', $creator->slug)
                    ->with('success', 'Conta criada com sucesso! Bem-vindo!');
            }
        }

        return redirect()->route('dashboard')
            ->with('success', 'Conta criada com sucesso! Bem-vindo!');
    }

    /**
     * Gera um slug único de até 10 caracteres (letras e números)
     */
    private function generateUniqueSlug(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $maxAttempts = 100; // Limite de tentativas para evitar loop infinito
        
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            // Gera um slug aleatório de 10 caracteres
            $slug = '';
            for ($i = 0; $i < 10; $i++) {
                $slug .= $characters[random_int(0, strlen($characters) - 1)];
            }
            
            // Verifica se o slug já existe
            if (!User::where('slug', $slug)->exists()) {
                return $slug;
            }
        }
        
        // Se após 100 tentativas não encontrou um slug único, usa timestamp + random
        return substr(str_shuffle($characters), 0, 8) . random_int(10, 99);
    }

    /**
     * Captura a indicação se houver referrer slug na sessão ou cookies
     * A indicação só é registrada no momento do cadastro
     * A indicação vale para qualquer assinatura, então não precisa do creator_id
     */
    private function captureReferral(User $newUser): void
    {
        // Tenta obter referrer slug da sessão primeiro, depois dos cookies (afiliado comum)
        $referrerSlug = session('referrer_slug') ?? request()->cookie('referrer_slug');

        if (!$referrerSlug) {
            return;
        }

        // Verifica se o usuário já tem uma indicação (não permite múltiplos indicadores)
        if (Referral::where('referred_user_id', $newUser->id)->exists()) {
            // Remove cookies mesmo se já tiver indicação
            $this->clearReferralCookies();
            return;
        }

        // Busca o usuário indicador pelo slug
        $referrer = User::where('slug', $referrerSlug)->first();
        if (!$referrer) {
            $this->clearReferralCookies();
            return;
        }

        // Não permite auto-indicação
        if ($referrer->id === $newUser->id) {
            $this->clearReferralCookies();
            return;
        }

        // Cria o registro de indicação (sem creator_id - indicação vale para qualquer assinatura)
        Referral::create([
            'referred_user_id' => $newUser->id,
            'referrer_user_id' => $referrer->id,
            'creator_id' => null, // Indicação vale para qualquer assinatura
            'referred_at' => now(),
        ]);

        // Remove sessão e cookies após capturar (conta criada)
        $this->clearReferralCookies();
    }

    /**
     * Remove os cookies e sessão de indicação após a conta ser criada
     */
    private function clearReferralCookies(): void
    {
        // Limpa a sessão (referrer_slug para indicação, creator_slug para redirecionamento, registration_url)
        session()->forget(['referrer_slug', 'creator_slug', 'registration_url']);
        
        // Remove os cookies (expira imediatamente)
        cookie()->queue(cookie()->forget('referrer_slug'));
        cookie()->queue(cookie()->forget('creator_slug'));
        cookie()->queue(cookie()->forget('registration_url'));
    }

    /**
     * Processa o logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Mostra o formulário de solicitação de recuperação de senha
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Processa a solicitação de recuperação de senha
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Não encontramos um usuário com esse endereço de e-mail.',
            ]);
        }

        // Gera o token de reset
        $token = Str::random(64);
        
        // Salva o token na tabela password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Envia o e-mail através da controller
        Mail::to($user->email)->send(new ResetPasswordMail($user, $token));

        return back()->with('status', 'Enviamos um link de recuperação de senha para o seu e-mail.');
    }

    /**
     * Mostra o formulário de redefinição de senha
     */
    public function showResetPasswordForm(Request $request)
    {
        // Obtém o token e email da query string (enviados pelo link do e-mail)
        $token = $request->query('token');
        $email = $request->query('email');

        // Valida se token e email foram fornecidos
        if (!$token || !$email) {
            return redirect()->route('password.request')
                ->with('error', 'Link de recuperação de senha inválido.');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Processa a redefinição de senha
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Não encontramos um usuário com esse endereço de e-mail.']);
        }

        // Verifica o token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return back()->withErrors(['token' => 'O token de recuperação é inválido ou expirou.']);
        }

        // Verifica se o token não expirou (60 minutos)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            return back()->withErrors(['token' => 'O token de recuperação expirou. Solicite um novo.']);
        }

        // Atualiza a senha
        $user->password = Hash::make($request->password);
        $user->save();

        // Remove o token usado
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return redirect()->route('login')->with('status', 'Sua senha foi redefinida com sucesso!');
    }

    /**
     * Mostra a página de confirmação de e-mail
     */
    public function showVerificationNotice()
    {
        // Verifica se há e-mail pendente na sessão
        $email = session('pending_verification_email') ?? request('email');
        
        if (!$email) {
            // Se não há e-mail na sessão, redireciona para login
            return redirect()->route('login')
                ->with('error', 'Sessão expirada. Por favor, faça login ou cadastre-se novamente.');
        }
        
        return view('auth.verify-email', compact('email'));
    }

    /**
     * Verifica o e-mail do usuário
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('login')
                ->with('error', 'Link de verificação inválido ou expirado. Solicite um novo.');
        }

        $user = User::findOrFail($id);

        // Verifica se o hash corresponde
        if (!hash_equals((string) $hash, sha1($user->email))) {
            return redirect()->route('login')
                ->with('error', 'Link de verificação inválido.');
        }

        // Verifica se já foi confirmado
        if ($user->email_verified_at) {
            return redirect()->route('login')
                ->with('status', 'Seu e-mail já foi confirmado. Você pode fazer login.');
        }

        // Confirma o e-mail
        $user->email_verified_at = now();
        $user->save();

        // Limpa a sessão de e-mail pendente
        session()->forget('pending_verification_email');

        // Faz login automaticamente após confirmar
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'E-mail confirmado com sucesso! Bem-vindo!');
    }

    /**
     * Reenvia o e-mail de confirmação
     */
    public function resendVerificationEmail(Request $request)
    {
        // Busca o e-mail da sessão (definido após cadastro ou tentativa de login)
        $email = session('pending_verification_email');
        
        if (!$email) {
            return back()->withErrors(['email' => 'Sessão expirada. Por favor, faça login ou cadastre-se novamente.']);
        }
        
        $user = User::where('email', $email)->first();

        if (!$user) {
            session()->forget('pending_verification_email');
            return back()->withErrors(['email' => 'Usuário não encontrado.']);
        }

        if ($user->email_verified_at) {
            // Limpa a sessão se o e-mail já foi confirmado
            session()->forget('pending_verification_email');
            return redirect()->route('login')
                ->with('info', 'Seu e-mail já foi confirmado. Você pode fazer login.');
        }

        $this->sendVerificationEmail($user);

        return back()->with('status', 'E-mail de confirmação reenviado com sucesso!');
    }

    /**
     * Envia e-mail de verificação
     */
    private function sendVerificationEmail(User $user)
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        Mail::to($user->email)->send(new VerifyEmailMail($user, $verificationUrl));
    }
}

