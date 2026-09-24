// Render em prod das telas: planos (criadora), conversa (dois lados), admin, saque. Com rollback.
//   ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && php artisan tinker" < tests/prod/chamada/t3_telas.php
view()->share('errors', new \Illuminate\Support\ViewErrorBag);
DB::beginTransaction();
try {
    \App\Models\PlatformSetting::setValue('video_calls_enabled', '1', 'teste');
    $criadora = \App\Models\User::where('creator_status', 'approved')->whereHas('subscriptionPlans')->orderByDesc('id')->first();
    $criadora->update(['video_call_enabled' => 1, 'video_call_price' => 100, 'video_call_minutes' => 15]);
    $fa = \App\Models\User::withoutGlobalScopes()->where('email', 'like', 'teste-onboarding-%')->orderByDesc('id')->first();
    $fa->getOrCreateWallet()->addBalance(250, null, 'saldo de teste');
    $conversa = \App\Models\Conversation::firstOrCreate(['creator_id' => $criadora->id, 'subscriber_id' => $fa->id]);
    $req = fn (array $d) => tap(\Illuminate\Http\Request::create('/x', 'POST', $d), fn ($r) => $r->headers->set('Accept', 'application/json'));
    $ctrl = app(\App\Http\Controllers\VideoCallController::class);
    $render = fn ($r) => $r instanceof \Illuminate\View\View ? $r->render() : (method_exists($r, 'getContent') ? $r->getContent() : (string) $r);

    Auth::login($criadora);
    $html = $render(app(\App\Http\Controllers\SubscriptionPlanController::class)->index());
    echo "planos: ", strlen($html), " bytes | video_call_price: ", substr_count($html, 'id="video_call_price"'), " | 'de cada chamada': ", substr_count($html, 'de cada chamada'), "\n";

    Auth::login($fa);
    $html = $render(app(\App\Http\Controllers\ChatController::class)->show($conversa->id));
    echo "chat fa (antes): ", strlen($html), " bytes | botao pedir: ", substr_count($html, 'abrirChamadaModal()'), " | modal: ", substr_count($html, 'id="chamadaModal"'), "\n";
    $ctrl->pedir($req(['suggested_at' => '2026-09-27T21:00']), $conversa->id);
    $html = $render(app(\App\Http\Controllers\ChatController::class)->show($conversa->id));
    echo "chat fa (depois): botao pedir: ", substr_count($html, 'abrirChamadaModal()'), " (esperado 0) | card: ", substr_count($html, 'class="chamada-card"'), " | payload com pode_marcar false: ", substr_count($html, '&quot;pode_marcar&quot;:false'), "\n";

    Auth::login($criadora);
    $html = $render(app(\App\Http\Controllers\ChatController::class)->show($conversa->id));
    echo "chat criadora: card: ", substr_count($html, 'class="chamada-card"'), " | pode_marcar true: ", substr_count($html, '&quot;pode_marcar&quot;:true'), "\n";

    $lista = $render(app(\App\Http\Controllers\ChatController::class)->index());
    echo "lista de conversas: 'Chamada de vídeo' aparece ", substr_count($lista, 'Chamada de vídeo'), "x\n";

    $admin = \App\Models\User::where('is_admin', 1)->first();
    Auth::login($admin);
    $html = $render(app(\App\Http\Controllers\Admin\PlatformSettingController::class)->index());
    echo "admin config: video_calls_enabled: ", substr_count($html, 'name="video_calls_enabled"'), " | tolerancia: ", substr_count($html, 'name="video_call_tolerance_minutes"'), "\n";
    $html = $render(app(\App\Http\Controllers\Admin\AdminVideoCallController::class)->index(\Illuminate\Http\Request::create('/admin/chamadas', 'GET')));
    echo "admin lista: ", strlen($html), " bytes | linhas 'Aguardando a criadora marcar': ", substr_count($html, 'Aguardando a criadora marcar'), " | menu 'Chamadas': ", substr_count($html, '>Chamadas<'), "\n";

    Auth::login($criadora);
    $wc = app(\App\Http\Controllers\WithdrawController::class);
    $rm = new \ReflectionMethod($wc, 'index');
    $html = $render($rm->getNumberOfParameters() > 0 ? $wc->index(\Illuminate\Http\Request::create('/withdraw', 'GET')) : $wc->index());
    echo "saque criadora: 'Reservado em chamadas': ", substr_count($html, 'Reservado em chamadas'), "\n";
} catch (\Throwable $e) {
    echo "EXCECAO ", get_class($e), ": ", $e->getMessage(), " @ ", $e->getFile(), ":", $e->getLine(), "\n";
} finally {
    DB::rollBack();
    echo "rollback feito; video_calls_enabled agora: ", var_export(\App\Models\PlatformSetting::getValue('video_calls_enabled'), true), "\n";
}
