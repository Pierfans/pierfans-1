<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to('/dashboard');
});

// Rotas de autenticação
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('/reset-password', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

    // Rotas de verificação de e-mail
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
});

    // Rotas de verificação de e-mail
    Route::get('/email/verify', [AuthController::class, 'showVerificationNotice'])->name('verification.notice');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->name('verification.resend');

// Rota de logout (requer autenticação)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard com feed de postagens
    Route::get('/dashboard', [\App\Http\Controllers\PostController::class, 'index'])->name('dashboard');

    // Rotas de Postagens
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/create', [\App\Http\Controllers\PostController::class, 'create'])->name('create');
        Route::get('/create-with-r2', function () {
            return view('posts.create-with-r2');
        })->name('create-with-r2');
        Route::post('/', [\App\Http\Controllers\PostController::class, 'store'])->name('store');
        Route::post('/upload-chunk', [\App\Http\Controllers\PostController::class, 'uploadChunk'])->name('upload-chunk');
        Route::get('/{id}/edit', [\App\Http\Controllers\PostController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\PostController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\PostController::class, 'destroy'])->name('destroy');
    });

    // Meu Conteúdo: lista de publicações do próprio criador para gerenciar
    Route::get('/meu-conteudo', [\App\Http\Controllers\PostController::class, 'myContent'])->name('posts.my-content');

    // Post Media (R2 presigned upload + confirm)
    Route::prefix('post-media')->name('post-media.')->group(function () {
        Route::post('/request-upload-url', [\App\Http\Controllers\PostMediaController::class, 'requestUploadUrl'])->name('request-upload-url');
        Route::post('/confirm-upload', [\App\Http\Controllers\PostMediaController::class, 'confirmUpload'])->name('confirm-upload');
        Route::get('/{id}/stream', [\App\Http\Controllers\PostMediaController::class, 'stream'])->name('stream');
    });

    // Rotas de Criador
    Route::prefix('creator')->name('creator.')->middleware('creator.onboarding')->group(function () {
        Route::get('/', [\App\Http\Controllers\CreatorController::class, 'index'])->name('index');
        Route::post('/save-step/{step}', [\App\Http\Controllers\CreatorController::class, 'saveStep'])->name('save-step');
        Route::post('/verificar', [\App\Http\Controllers\CreatorController::class, 'startVerification'])->name('verificar');
        Route::get('/get-data', [\App\Http\Controllers\CreatorController::class, 'getData'])->name('get-data');
    });

    // Documento de identidade do criador: fora do prefixo 'creator' de proposito, porque o admin
    // (que nao passa pelo onboarding) tambem precisa abrir. A autorizacao esta no controller:
    // so o dono do documento ou um admin. Antes esses arquivos eram publicos em /_files_/documents.
    Route::get('/documento-criador/{userId}/{tipo}', [\App\Http\Controllers\CreatorDocumentController::class, 'show'])
        ->whereNumber('userId')->name('creator.documento');

    // Rotas de Planos de Assinatura
    Route::prefix('subscription-plans')->name('subscription-plans.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SubscriptionPlanController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\SubscriptionPlanController::class, 'store'])->name('store');
    });

    // Rotas de Perfil
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/edit', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [\App\Http\Controllers\ProfileController::class, 'update'])->name('update');
        Route::post('/cover-photo', [\App\Http\Controllers\ProfileController::class, 'updateCoverPhoto'])->name('update-cover-photo');
        Route::post('/cover-photo/delete', [\App\Http\Controllers\ProfileController::class, 'deleteCoverPhoto'])->name('delete-cover-photo');
        Route::post('/profile-photo', [\App\Http\Controllers\ProfileController::class, 'updateProfilePhoto'])->name('update-profile-photo');
        Route::get('/creator/{userId}/plans', [\App\Http\Controllers\ProfileController::class, 'getCreatorPlans'])->name('creator.plans');
    });

    // Rotas de Dados de Identificação
    Route::prefix('user-identification')->name('user-identification.')->group(function () {
        Route::get('/create', [\App\Http\Controllers\UserIdentificationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\UserIdentificationController::class, 'store'])->name('store');
    });

    // Rotas de Checkout
    Route::prefix('checkout')->name('checkout.')->group(function () {
        // Rota de sucesso deve vir ANTES das rotas genéricas para evitar conflito
        Route::get('/success/{subscriptionId}', [\App\Http\Controllers\CheckoutController::class, 'success'])->name('success');
        Route::get('/transaction/{transactionId}/status', [\App\Http\Controllers\CheckoutController::class, 'checkTransactionStatus'])->name('transaction.status');
        Route::get('/{planId}/{method}', [\App\Http\Controllers\CheckoutController::class, 'show'])->name('show');
        Route::post('/{planId}/{method}', [\App\Http\Controllers\CheckoutController::class, 'process'])->name('process');
    });

    // Rotas de Conteúdo Único (PPV)
    Route::prefix('ppv')->name('ppv.')->group(function () {
        Route::get('/success/{purchase}',                 [\App\Http\Controllers\PPVCheckoutController::class, 'success'])->name('success');
        Route::get('/transaction/{transactionId}/status', [\App\Http\Controllers\PPVCheckoutController::class, 'checkStatus'])->name('transaction.status');
        Route::get('/{post}/{method}',                    [\App\Http\Controllers\PPVCheckoutController::class, 'show'])->name('show');
        Route::post('/{post}/{method}',                   [\App\Http\Controllers\PPVCheckoutController::class, 'process'])->name('process');
    });

    // Rotas de Assinantes
    Route::prefix('subscribers')->name('subscribers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SubscriberController::class, 'index'])->name('index');
    });

    // Rotas de Assinaturas do Usuário
    Route::prefix('my-subscriptions')->name('my-subscriptions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\UserSubscriptionController::class, 'index'])->name('index');
    });

    // Rotas de Denúncias
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::post('/', [\App\Http\Controllers\ReportController::class, 'store'])->name('store');
    });

    // Rotas de Afiliados (comum)
    Route::prefix('affiliates')->name('affiliates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AffiliateController::class, 'index'])->name('index');
        Route::get('/extract', [\App\Http\Controllers\AffiliateController::class, 'extract'])->name('extract');
        Route::get('/withdraw', [\App\Http\Controllers\AffiliateController::class, 'showWithdraw'])->name('withdraw');
        Route::post('/withdraw', [\App\Http\Controllers\AffiliateController::class, 'storeWithdraw'])->name('withdraw.store');
        Route::get('/commission/{subscriptionId}', [\App\Http\Controllers\AffiliateController::class, 'getCommissionDetails'])->name('commission.details');
        // Rotas de contas bancárias (reutilizando lógica do WithdrawController)
        Route::get('/bank-account/{id}', [\App\Http\Controllers\WithdrawController::class, 'getBankAccount'])->name('bank-account.get');
        Route::post('/bank-account', [\App\Http\Controllers\WithdrawController::class, 'storeBankAccount'])->name('bank-account.store');
        Route::put('/bank-account/{id}', [\App\Http\Controllers\WithdrawController::class, 'updateBankAccount'])->name('bank-account.update');
        Route::delete('/bank-account/{id}', [\App\Http\Controllers\WithdrawController::class, 'deleteBankAccount'])->name('bank-account.delete');
    });


    // Rotas de Saque (apenas criadores aprovados)
    Route::prefix('withdraw')->name('withdraw.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WithdrawController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\WithdrawController::class, 'store'])->name('store');
        Route::get('/bank-account/{id}', [\App\Http\Controllers\WithdrawController::class, 'getBankAccount'])->name('bank-account.get');
        Route::post('/bank-account', [\App\Http\Controllers\WithdrawController::class, 'storeBankAccount'])->name('bank-account.store');
        Route::put('/bank-account/{id}', [\App\Http\Controllers\WithdrawController::class, 'updateBankAccount'])->name('bank-account.update');
        Route::delete('/bank-account/{id}', [\App\Http\Controllers\WithdrawController::class, 'deleteBankAccount'])->name('bank-account.delete');
    });

    // Rotas de Extrato (apenas criadores aprovados)
    Route::prefix('extract')->name('extract.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ExtractController::class, 'index'])->name('index');
    });

    // Rotas de Postagens (apenas criadores aprovados)
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/create', [\App\Http\Controllers\PostController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\PostController::class, 'store'])->name('store');

        // Interações com postagens
        Route::post('/{postId}/like', [\App\Http\Controllers\PostInteractionController::class, 'toggleLike'])->name('like');
        Route::get('/{postId}/comments', [\App\Http\Controllers\PostInteractionController::class, 'getComments'])->name('comments');
        Route::post('/{postId}/comments', [\App\Http\Controllers\PostInteractionController::class, 'createComment'])->name('comment.create');
    });

    // Rotas de Chat
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ChatController::class, 'index'])->name('index');
        Route::get('/new', [\App\Http\Controllers\ChatController::class, 'newMessage'])->name('new');
        Route::get('/search-users', [\App\Http\Controllers\ChatController::class, 'searchUsers'])->name('search-users');
        Route::get('/start/{userId}', [\App\Http\Controllers\ChatController::class, 'startConversation'])->name('start');
        Route::get('/{conversationId}', [\App\Http\Controllers\ChatController::class, 'show'])->name('show');
        Route::post('/{conversationId}/message', [\App\Http\Controllers\ChatController::class, 'sendMessage'])->name('send-message');
        Route::get('/{conversationId}/messages', [\App\Http\Controllers\ChatController::class, 'getNewMessages'])->name('get-messages');
    });

    // Rotas de interações com comentários
    Route::post('/comments/{commentId}/like', [\App\Http\Controllers\PostInteractionController::class, 'toggleCommentLike'])->name('comment.like');

    // Rotas de Carteira
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WalletController::class, 'index'])->name('index');
        Route::post('/process-add-balance/{method}', [\App\Http\Controllers\WalletController::class, 'processAddBalance'])->name('process-add-balance');
        Route::get('/transaction/{transactionId}/status', [\App\Http\Controllers\WalletController::class, 'checkTransactionStatus'])->name('transaction.status');
    });

    // Rotas Admin (requer autenticação E permissão de admin)
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        // Dashboard Admin
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Usuários
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('index');
            Route::get('/search', [\App\Http\Controllers\Admin\AdminUserController::class, 'search'])->name('search');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('update');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminUserController::class, 'show'])->name('show');
            Route::post('/{id}/add-credit', [\App\Http\Controllers\Admin\AdminUserController::class, 'addCredit'])->name('add-credit');
        });

        // Criadores
        Route::prefix('creators')->name('creators.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'show'])->name('show');
            Route::get('/{id}/documentos', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'documents'])->name('documents');
            Route::post('/{id}/approve', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'reject'])->name('reject');
            Route::post('/{id}/toggle-active', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'toggleActive'])->name('toggle-active');
        });

        // TOP Criadores
        Route::prefix('top-creators')->name('top-creators.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminTopCreatorsController::class, 'index'])->name('index');
            Route::post('/{id}/toggle', [\App\Http\Controllers\Admin\AdminTopCreatorsController::class, 'toggle'])->name('toggle');
            Route::post('/order', [\App\Http\Controllers\Admin\AdminTopCreatorsController::class, 'updateOrder'])->name('order');
        });

        // Postagens
        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminPostController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminPostController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminPostController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminPostController::class, 'update'])->name('update');
            Route::post('/{id}/disable', [\App\Http\Controllers\Admin\AdminPostController::class, 'disable'])->name('disable');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminPostController::class, 'destroy'])->name('destroy');
            Route::delete('/{postId}/media/{mediaId}', [\App\Http\Controllers\Admin\AdminPostController::class, 'deleteMedia'])->name('media.delete');
            Route::delete('/{id}/media', [\App\Http\Controllers\Admin\AdminPostController::class, 'deleteAllMedia'])->name('media.delete-all');
        });

        // Lixeira
        Route::prefix('trash')->name('trash.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminTrashController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminTrashController::class, 'show'])->name('show');
            Route::post('/{id}/restore', [\App\Http\Controllers\Admin\AdminTrashController::class, 'restore'])->name('restore');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminTrashController::class, 'destroy'])->name('destroy');
        });

        // Denúncias
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminReportController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminReportController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [\App\Http\Controllers\Admin\AdminReportController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\Admin\AdminReportController::class, 'reject'])->name('reject');
        });

        // Configurações da Plataforma
        Route::prefix('platform-settings')->name('platform-settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PlatformSettingController::class, 'index'])->name('index');
            Route::put('/', [\App\Http\Controllers\Admin\PlatformSettingController::class, 'update'])->name('update');
        });

        // Saques
        Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminWithdrawalController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminWithdrawalController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [\App\Http\Controllers\Admin\AdminWithdrawalController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\Admin\AdminWithdrawalController::class, 'reject'])->name('reject');
        });

        // Carteiras
        Route::prefix('wallets')->name('wallets.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminWalletController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminWalletController::class, 'show'])->name('show');
            Route::post('/{id}/add-balance', [\App\Http\Controllers\Admin\AdminWalletController::class, 'addBalance'])->name('add-balance');
        });

        // Fluxo de Caixa (ledger)
        Route::prefix('fluxo-caixa')->name('fluxo-caixa.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminLedgerController::class, 'index'])->name('index');
            Route::post('/importar-extrato', [\App\Http\Controllers\Admin\AdminLedgerController::class, 'importExtrato'])->name('importar-extrato');
            Route::post('/sacar', [\App\Http\Controllers\Admin\AdminLedgerController::class, 'sacar'])->name('sacar');
        });

        // Vendas por Criador (desempenho dos influenciadores)
        Route::prefix('vendas')->name('vendas.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminSalesController::class, 'index'])->name('index');
            Route::get('/{creatorId}', [\App\Http\Controllers\Admin\AdminSalesController::class, 'show'])->name('show');
        });

        // Posts em Destaque
        Route::prefix('featured-posts')->name('featured-posts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminFeaturedPostController::class, 'index'])->name('index');
            Route::post('/{id}/toggle-login', [\App\Http\Controllers\Admin\AdminFeaturedPostController::class, 'toggleLogin'])->name('toggle-login');
            Route::post('/{id}/toggle-dashboard', [\App\Http\Controllers\Admin\AdminFeaturedPostController::class, 'toggleDashboard'])->name('toggle-dashboard');
        });

    });
});

// Rotas públicas
// Busca de criadores (pública)
Route::get('/search', [\App\Http\Controllers\CreatorSearchController::class, 'index'])->name('creator.search');

// Rotas de tracking de afiliados (devem vir antes das rotas de perfil)
Route::get('/a/{referrerSlug}/{creatorSlug}', [\App\Http\Controllers\AffiliateTrackingController::class, 'trackWithCreator'])->name('affiliate.track.creator');
Route::get('/a/{slug}', [\App\Http\Controllers\AffiliateTrackingController::class, 'track'])->name('affiliate.track');

// Rotas públicas de perfil (deve ficar por último para não conflitar com outras rotas)
// Rota de perfil com suporte a dois slugs (referrer/creator)
Route::get('/{referrerSlug}/{creatorSlug}', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show.referral');
Route::get('/{username}', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
