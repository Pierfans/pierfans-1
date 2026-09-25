// Segunda entrega: entrar na sala (janela, done, token), webhook, robo fechando sala, admin devolvendo done. Com rollback.
//   ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && php artisan tinker" < tests/prod/chamada/t4_sala.php
view()->share('errors', new \Illuminate\Support\ViewErrorBag);
DB::beginTransaction();
try {
    \App\Models\PlatformSetting::setValue('video_calls_enabled', '1', 'teste');
    $criadora = \App\Models\User::where('creator_status', 'approved')->orderByDesc('id')->first();
    $fa = \App\Models\User::withoutGlobalScopes()->where('email', 'like', 'teste-onboarding-%')->orderByDesc('id')->first();
    $conversa = \App\Models\Conversation::firstOrCreate(['creator_id' => $criadora->id, 'subscriber_id' => $fa->id]);
    $lk = app(\App\Services\LiveKitService::class);
    echo "livekit configurado: ", var_export($lk->configurado(), true), "\n";
    $nova = fn (array $extra) => \App\Models\VideoCall::create(array_merge(['conversation_id' => $conversa->id, 'creator_id' => $criadora->id, 'user_id' => $fa->id, 'price' => 100, 'duration_minutes' => 15, 'amount_paid' => 100, 'platform_percentage' => 20, 'platform_amount' => 15, 'affiliate_amount' => 5, 'creator_amount' => 80], $extra));
    $ctrl = app(\App\Http\Controllers\VideoCallController::class);
    $tipo = fn ($r) => $r instanceof \Illuminate\View\View ? 'VIEW' : (method_exists($r, 'getTargetUrl') ? 'REDIRECT ' . $r->getTargetUrl() . ' | ' . ($r->getSession()->get('error') ?? '') : get_class($r));

    // fora da janela: redireciona
    $c1 = $nova(['status' => 'scheduled', 'scheduled_at' => now()->addHours(2)]);
    Auth::login($criadora);
    echo "criadora 2h antes -> ", $tipo($ctrl->entrar($c1, $lk)), " | status=", $c1->fresh()->status, " (esperado scheduled)\n";

    // na janela: fa entra primeiro (nao muda), depois criadora (vira done)
    $c2 = $nova(['status' => 'scheduled', 'scheduled_at' => now()->addMinutes(5)]);
    Auth::login($fa);
    $r = $ctrl->entrar($c2, $lk);
    echo "fa na janela -> ", $tipo($r), " | status=", $c2->fresh()->status, " (esperado scheduled) | esperando=", var_export($r->getData()['esperando'] ?? null, true), "\n";
    Auth::login($criadora);
    $r = $ctrl->entrar($c2->fresh(), $lk);
    $c2->refresh();
    echo "criadora na janela -> ", $tipo($r), " | status={$c2->status} (esperado done) joined={$c2->creator_joined_at} room={$c2->room_name} | fimEpoch-agora=", ($r->getData()['fimEpoch'] ?? 0) - time(), "s (esperado ~900)\n";
    $tok = $r->getData()['token'];
    $claims = \App\Support\LiveKitJwt::verify($tok, config('services.livekit.api_secret'));
    echo "token: sub=", $claims['sub'] ?? '-', " room=", $claims['video']['room'] ?? '-', " exp-agora=", ($claims['exp'] ?? 0) - time(), "s (esperado ~900)\n";
    echo "mensagem 'Entrou na chamada': ", \App\Models\Message::where('video_call_id', $c2->id)->where('content', 'Entrou na chamada.')->count(), "\n";
    $html = $r->render();
    echo "pagina: ", strlen($html), " bytes | livekit-client: ", substr_count($html, 'livekit-client'), " | id=\"tempo\": ", substr_count($html, 'id="tempo"'), "\n";
    $ctrl->entrar($c2->fresh(), $lk);
    echo "criadora 2x: mensagens 'Entrou': ", \App\Models\Message::where('video_call_id', $c2->id)->where('content', 'Entrou na chamada.')->count(), " (esperado 1)\n";
    // card do chat: pode_entrar e entrar_url
    $p = $c2->fresh()->toPayload($fa);
    echo "payload fa: pode_entrar=", var_export($p['pode_entrar'], true), " entrar_url=", $p['entrar_url'] ?? '-', "\n";

    // webhook: assinatura errada, participant_joined do fa, room_finished, repetido
    $wh = app(\App\Http\Controllers\LiveKitWebhookController::class);
    $corpo = json_encode(['event' => 'participant_joined', 'room' => ['name' => $c2->room_name], 'participant' => ['identity' => (string) $fa->id]]);
    $assina = fn ($body, $secret) => \App\Support\LiveKitJwt::sign(config('services.livekit.api_key'), $secret, ['sha256' => base64_encode(hash('sha256', $body, true))], 60);
    $req = fn ($body, $auth) => \Illuminate\Http\Request::create('/api/livekit/webhook', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => $auth], $body);
    echo "webhook assinatura errada -> ", $wh->handle($req($corpo, $assina($corpo, 'errado')), $lk)->getStatusCode(), " (esperado 401)\n";
    echo "webhook joined fa -> ", $wh->handle($req($corpo, $assina($corpo, config('services.livekit.api_secret'))), $lk)->getStatusCode(), " user_joined_at=", $c2->fresh()->user_joined_at, "\n";
    $primeiro = $c2->fresh()->user_joined_at;
    sleep(1);
    $wh->handle($req($corpo, $assina($corpo, config('services.livekit.api_secret'))), $lk);
    echo "webhook repetido: user_joined_at igual? ", var_export((string) $c2->fresh()->user_joined_at === (string) $primeiro, true), "\n";
    $fimCorpo = json_encode(['event' => 'room_finished', 'room' => ['name' => $c2->room_name]]);
    $wh->handle($req($fimCorpo, $assina($fimCorpo, config('services.livekit.api_secret'))), $lk);
    echo "webhook room_finished -> ended_at=", $c2->fresh()->ended_at, " | status=", $c2->fresh()->status, " (continua done)\n";

    // robo: done com duracao vencida e sem ended_at -> fecha (DeleteRoom numa sala que nao existe = ok)
    $c3 = $nova(['status' => 'done', 'scheduled_at' => now()->subMinutes(30), 'creator_joined_at' => now()->subMinutes(16), 'room_name' => 'chamada-teste-robo']);
    \Illuminate\Support\Facades\Artisan::call('chamadas:rodar');
    echo trim(\Illuminate\Support\Facades\Artisan::output()), "\n";
    echo "c3 ended_at=", $c3->fresh()->ended_at, " (esperado agora) | c2 ended_at mantido: ", var_export((string) $c2->fresh()->ended_at !== '', true), "\n";

    // admin devolve done
    $adm = app(\App\Http\Controllers\Admin\AdminVideoCallController::class);
    $admin = \App\Models\User::where('is_admin', 1)->first();
    Auth::login($admin);
    $saldoAntes = (float) $fa->fresh()->getOrCreateWallet()->balance;
    $adm->devolver($c2->fresh());
    echo "admin devolve done -> status=", $c2->fresh()->status, "/", $c2->fresh()->refund_reason, " saldo fa +", (float) $fa->fresh()->getOrCreateWallet()->balance - $saldoAntes, " (esperado 100)\n";
    echo "admin devolve 2x -> status continua ", $c2->fresh()->status, " saldo fa +", (float) $fa->fresh()->getOrCreateWallet()->balance - $saldoAntes, " (esperado 100)\n";
    $html = $adm->index(\Illuminate\Http\Request::create('/admin/chamadas', 'GET'))->render();
    echo "admin lista: 'Este mês': ", substr_count($html, 'Este mês'), " | 'Devolver ao fã': ", substr_count($html, 'Devolver ao fã'), "\n";
} catch (\Throwable $e) {
    echo "EXCECAO ", get_class($e), ": ", $e->getMessage(), " @ ", $e->getFile(), ":", $e->getLine(), "\n";
} finally {
    DB::rollBack();
    echo "rollback feito; video_calls_enabled agora: ", var_export(\App\Models\PlatformSetting::getValue('video_calls_enabled'), true), "\n";
}
