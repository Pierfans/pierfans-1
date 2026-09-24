// Robô: no_show, expired, idempotência, done nunca devolve. Com rollback.
//   ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && php artisan tinker" < tests/prod/chamada/t2_robo.php
DB::beginTransaction();
try {
    $criadora = \App\Models\User::where('creator_status', 'approved')->orderByDesc('id')->first();
    $fa = \App\Models\User::withoutGlobalScopes()->where('email', 'like', 'teste-onboarding-%')->orderByDesc('id')->first();
    $conversa = \App\Models\Conversation::firstOrCreate(['creator_id' => $criadora->id, 'subscriber_id' => $fa->id]);
    $wallet = $fa->getOrCreateWallet();
    $base = (float) $wallet->balance;

    $nova = function (array $extra) use ($conversa, $criadora, $fa) {
        return \App\Models\VideoCall::create(array_merge([
            'conversation_id' => $conversa->id, 'creator_id' => $criadora->id, 'user_id' => $fa->id,
            'price' => 100, 'duration_minutes' => 15, 'amount_paid' => 100, 'platform_percentage' => 20,
            'platform_amount' => 15, 'affiliate_amount' => 5, 'creator_amount' => 80,
        ], $extra));
    };
    // created_at manual: o create ignora, então força depois
    $set = fn ($c, $created) => \DB::table('video_calls')->where('id', $c->id)->update(['created_at' => $created]);

    $a = $nova(['status' => 'scheduled', 'scheduled_at' => now()->subMinutes(31)]);          // no_show
    $b = $nova(['status' => 'scheduled', 'scheduled_at' => now()->subMinutes(29)]);          // ainda não
    $c = $nova(['status' => 'requested']); $set($c, now()->subDays(8));                       // expired
    $d = $nova(['status' => 'requested']); $set($d, now()->subDays(6));                       // ainda não
    $e = $nova(['status' => 'done', 'scheduled_at' => now()->subDays(40), 'creator_joined_at' => now()->subDays(40)]); $set($e, now()->subDays(41)); // nunca
    $f = $nova(['status' => 'scheduled', 'scheduled_at' => now()->addDays(5)]); $set($f, now()->subDays(31)); // teto 30 dias

    \Illuminate\Support\Facades\Artisan::call('chamadas:rodar');
    echo \Illuminate\Support\Facades\Artisan::output();
    foreach (['a' => [$a, 'refunded/no_show'], 'b' => [$b, 'scheduled/'], 'c' => [$c, 'refunded/expired'], 'd' => [$d, 'requested/'], 'e' => [$e, 'done/'], 'f' => [$f, 'refunded/expired']] as $k => [$ch, $esp]) {
        $ch = $ch->fresh();
        echo "$k: {$ch->status}/{$ch->refund_reason} (esperado $esp)\n";
    }
    echo "saldo fa: ", $fa->fresh()->getOrCreateWallet()->balance - $base, " a mais (esperado 300)\n";

    \Illuminate\Support\Facades\Artisan::call('chamadas:rodar');
    echo "2a rodada: ", trim(\Illuminate\Support\Facades\Artisan::output()), " | saldo fa: ", $fa->fresh()->getOrCreateWallet()->balance - $base, " a mais (esperado 300)\n";

    // saldo da criadora: done de 40 dias atrás está liberado; done de hoje está a liberar
    $g = $nova(['status' => 'done', 'scheduled_at' => now(), 'creator_joined_at' => now()]);
    echo "criadora liberado(chamadas)=", \App\Models\VideoCall::creatorAmount($criadora->id, true), " (esperado 80) a liberar=", \App\Models\VideoCall::creatorAmount($criadora->id, false), " (esperado 80) reservado=", \App\Models\VideoCall::reservado($criadora->id), " (esperado 160: b e d)\n";
    echo "getAvailableBalance inclui? ", $criadora->getAvailableBalance() >= 80 ? 'sim' : 'NAO', " | getPendingBalance >= 80? ", $criadora->getPendingBalance() >= 80 ? 'sim' : 'NAO', "\n";
} catch (\Throwable $e) {
    echo "EXCECAO ", get_class($e), ": ", $e->getMessage(), " @ ", $e->getFile(), ":", $e->getLine(), "\n";
} finally {
    DB::rollBack();
    echo "rollback feito\n";
}
