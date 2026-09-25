// Chamada de vídeo, primeira entrega: pedido, pagamento, marcar, remarcar, recusar. Tudo com rollback.
//   ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && php artisan tinker" < tests/prod/chamada/t1_pedido.php
DB::beginTransaction();
try {
    \App\Models\PlatformSetting::setValue('video_calls_enabled', '1', 'teste');
    $criadora = \App\Models\User::where('creator_status', 'approved')->whereHas('subscriptionPlans')->orderByDesc('id')->first();
    $criadora->update(['video_call_enabled' => 1, 'video_call_price' => 100, 'video_call_minutes' => 15]);
    $fa = \App\Models\User::withoutGlobalScopes()->where('email', 'like', 'teste-onboarding-%')->orderByDesc('id')->first();
    $fa->getOrCreateWallet()->addBalance(250, null, 'saldo de teste');
    $conversa = \App\Models\Conversation::firstOrCreate(['creator_id' => $criadora->id, 'subscriber_id' => $fa->id]);
    echo "criadora {$criadora->id} @{$criadora->username} | fa {$fa->id} | conversa {$conversa->id} | saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, "\n";

    $ctrl = app(\App\Http\Controllers\VideoCallController::class);
    $req = fn (array $dados) => tap(\Illuminate\Http\Request::create('/x', 'POST', $dados), fn ($r) => $r->headers->set('Accept', 'application/json'));

    // 1. criadora tentando pedir: recusa
    Auth::login($criadora);
    echo "criadora pede -> ", $ctrl->pedir($req([]), $conversa->id)->getStatusCode(), " (esperado 400)\n";

    // 2. fa pede com sugestao sabado 21h Brasilia
    Auth::login($fa);
    $r = $ctrl->pedir($req(['suggested_at' => '2026-09-27T21:00']), $conversa->id);
    $d = json_decode($r->getContent(), true);
    echo "fa pede -> ", $r->getStatusCode(), " ", $d['message'], "\n";
    $chamada = \App\Models\VideoCall::orderByDesc('id')->first();
    echo "  status={$chamada->status} amount={$chamada->amount_paid} criadora={$chamada->creator_amount} plataforma={$chamada->platform_amount} afiliado={$chamada->affiliate_amount} soma=", round($chamada->creator_amount + $chamada->platform_amount + $chamada->affiliate_amount, 2), "\n";
    echo "  sugestao UTC={$chamada->suggested_at} (esperado 2026-09-28 00:00:00) | saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, " (esperado 150)\n";
    echo "  mensagem: ", $chamada->message->content, " | tipo ", $chamada->message->message_type, "\n";

    // 3. segundo pedido com um aberto: recusa
    echo "segundo pedido -> ", $ctrl->pedir($req([]), $conversa->id)->getStatusCode(), " (esperado 400)\n";

    // 4. pagar de novo a mesma chamada: idempotente
    \App\Http\Controllers\VideoCallController::pagar($chamada);
    echo "pagar 2x -> saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, " (esperado 150)\n";

    // 5. saldo da criadora: reservado, nao liberado nem a liberar
    echo "reservado=", \App\Models\VideoCall::reservado($criadora->id), " liberado(chamadas)=", \App\Models\VideoCall::creatorAmount($criadora->id, true), " a liberar(chamadas)=", \App\Models\VideoCall::creatorAmount($criadora->id, false), "\n";

    // 6. criadora marca (vazio = agora), remarca
    Auth::login($criadora);
    $r = $ctrl->marcar($req([]), $chamada->fresh());
    echo "marcar agora -> ", $r->getStatusCode(), " status=", $chamada->fresh()->status, " scheduled_at=", $chamada->fresh()->scheduled_at, "\n";
    $r = $ctrl->marcar($req(['scheduled_at' => '2026-09-27T21:00']), $chamada->fresh());
    echo "remarcar -> ", $r->getStatusCode(), " scheduled_at=", $chamada->fresh()->scheduled_at, " (esperado 2026-09-28 00:00:00) | payload: ", json_encode(json_decode($r->getContent(), true)['chat_message']['video_call']['status_label'], JSON_UNESCAPED_UNICODE), "\n";
    echo "horario passado -> ", $ctrl->marcar($req(['scheduled_at' => '2020-01-01T10:00']), $chamada->fresh())->getStatusCode(), " (esperado 400)\n";

    // 7. recusa: devolve exato, 2a recusa nao dobra
    $r = $ctrl->recusar($chamada->fresh());
    echo "recusar -> ", $r->getStatusCode(), " status=", $chamada->fresh()->status, "/", $chamada->fresh()->refund_reason, " saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, " (esperado 250)\n";
    echo "recusar 2x -> ", $ctrl->recusar($chamada->fresh())->getStatusCode(), " (esperado 400) saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, " (esperado 250)\n";

    // 8. sem saldo: recarga com o id
    $fa->fresh()->getOrCreateWallet()->subtractBalance(200, 'zera');
    Auth::login($fa->fresh()); // fresh: no tinker o objeto antigo guarda a carteira em cache; numa requisicao real o usuario e sempre novo
    $r = $ctrl->pedir($req([]), $conversa->id);
    $d = json_decode($r->getContent(), true);
    echo "sem saldo -> ", $r->getStatusCode(), " recarregar=", var_export($d['recarregar'] ?? null, true), " redirect=", $d['redirect'] ?? '-', "\n";
    $aguardando = \App\Models\VideoCall::orderByDesc('id')->first();
    echo "  status={$aguardando->status} (esperado awaiting_payment) | abertas na conversa: ", \App\Models\VideoCall::where('conversation_id', $conversa->id)->whereIn('status', \App\Models\VideoCall::ABERTAS)->count(), " (esperado 0)\n";
    // recarga cai (simula o webhook): PaymentTransaction com video_call_id + credito + concluir
    $fa->fresh()->getOrCreateWallet()->addBalance(100, null, 'recarga simulada');
    $tx = \App\Models\PaymentTransaction::create(['user_id' => $fa->id, 'video_call_id' => $aguardando->id, 'request_number' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'pix', 'status' => 'paid_out', 'amount' => 100]);
    \App\Http\Controllers\VideoCallController::concluirAposRecarga($tx);
    echo "  apos recarga: status=", $aguardando->fresh()->status, " (esperado requested) saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, " (esperado 50)\n";
} catch (\Throwable $e) {
    echo "EXCECAO ", get_class($e), ": ", $e->getMessage(), " @ ", $e->getFile(), ":", $e->getLine(), "\n";
} finally {
    DB::rollBack();
    echo "rollback feito; video_calls_enabled agora: ", var_export(\App\Models\PlatformSetting::getValue('video_calls_enabled'), true), "\n";
}
