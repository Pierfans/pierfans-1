<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chamada com {{ $outroNome }} - {{ config('app.name') }}</title>
    <script src="https://cdn.jsdelivr.net/npm/livekit-client@2/dist/livekit-client.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #111; color: #fff; font-family: system-ui, sans-serif; height: 100dvh; display: flex; flex-direction: column; }
        #remoto { flex: 1; position: relative; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        #remoto video.remoto { width: 100%; height: 100%; object-fit: contain; }
        #local { position: absolute; right: 12px; bottom: 12px; width: 28%; max-width: 180px; aspect-ratio: 3/4; background: #222; border-radius: 12px; overflow: hidden; border: 2px solid #333; }
        #local video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        #topo { position: absolute; top: 0; left: 0; right: 0; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(rgba(0,0,0,.6), transparent); font-size: 14px; z-index: 2; }
        #aviso { color: #ccc; text-align: center; padding: 24px; font-size: 16px; line-height: 1.5; position: absolute; z-index: 1; }
        #barra { display: flex; gap: 12px; justify-content: center; padding: 14px; background: #181818; }
        #barra button { border: 0; border-radius: 999px; padding: 12px 18px; font-size: 15px; font-weight: 600; cursor: pointer; background: #333; color: #fff; }
        #barra button.off { background: #b91c1c; }
        #sair { background: #e11d48 !important; }
        #tempo { font-variant-numeric: tabular-nums; font-weight: 700; }
    </style>
</head>
<body>
    <div id="remoto">
        <div id="topo">
            <span>{{ $outroNome }}</span>
            <span id="tempo">{{ $esperando ? 'aguardando a criadora' : '' }}</span>
        </div>
        <div id="aviso">Conectando...</div>
        <div id="local"></div>
    </div>
    <div id="barra">
        <button id="mic" type="button">Microfone</button>
        <button id="cam" type="button">Câmera</button>
        <button id="sair" type="button">Sair</button>
    </div>

<script>
(function () {
    const token = @json($token);
    const wsUrl = @json($wsUrl);
    let fimEpoch = @json($fimEpoch);            // null enquanto a criadora não entrou
    const estadoUrl = @json(route('chat.chamada.estado', $chamada->id));
    const voltar = @json(route('chat.show', $chamada->conversation_id));
    const aviso = document.getElementById('aviso');
    const remoto = document.getElementById('remoto');
    const local = document.getElementById('local');
    const tempo = document.getElementById('tempo');

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        aviso.innerHTML = 'Este navegador não libera a câmera (o do Instagram faz isso).<br>Abra este link no Chrome ou no Safari.';
        return;
    }
    if (!window.LivekitClient) {
        aviso.textContent = 'Não foi possível carregar o vídeo. Recarregue a página.';
        return;
    }

    const { Room, RoomEvent, Track } = LivekitClient;
    const room = new Room({ adaptiveStream: true, dynacast: true });
    let encerrou = false;

    function encerrar(msg) {
        if (encerrou) return;
        encerrou = true;
        room.disconnect();
        aviso.style.display = 'block';
        aviso.textContent = msg;
        setTimeout(() => { window.location.href = voltar; }, 2500);
    }

    // Cronômetro: fim vem do servidor em epoch. Zerou, desconecta. O servidor também derruba a sala.
    let cronometro = null;
    function iniciarCronometro() {
        if (cronometro || !fimEpoch) return;
        const tick = () => {
            const s = Math.max(0, fimEpoch - Math.floor(Date.now() / 1000));
            tempo.textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
            if (s <= 0) encerrar('Tempo esgotado. A chamada terminou.');
        };
        tick();
        cronometro = setInterval(tick, 1000);
    }
    iniciarCronometro();

    // Fã que entrou antes da criadora: pergunta ao servidor até ela entrar (e também quando
    // alguém conecta na sala), aí o cronômetro começa com o fim contado no servidor.
    function consultarEstado() {
        if (fimEpoch) return;
        fetch(estadoUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => { if (d.fimEpoch) { fimEpoch = d.fimEpoch; iniciarCronometro(); } })
            .catch(() => {});
    }
    if (!fimEpoch) setInterval(consultarEstado, 5000);

    room
        .on(RoomEvent.TrackSubscribed, (track) => {
            if (track.kind === Track.Kind.Video || track.kind === Track.Kind.Audio) {
                const el = track.attach();
                if (track.kind === Track.Kind.Video) { remoto.querySelectorAll('video.remoto').forEach(v => v.remove()); el.className = 'remoto'; }
                remoto.appendChild(el);
                aviso.style.display = 'none';
            }
        })
        .on(RoomEvent.TrackUnsubscribed, (track) => { track.detach().forEach(el => el.remove()); })
        .on(RoomEvent.ParticipantConnected, () => { consultarEstado(); })
        .on(RoomEvent.ParticipantDisconnected, () => { aviso.style.display = 'block'; aviso.textContent = 'A outra pessoa saiu.'; })
        .on(RoomEvent.Disconnected, () => { if (!encerrou) encerrar('Chamada encerrada.'); });

    room.connect(wsUrl, token).then(async () => {
        aviso.textContent = fimEpoch ? 'Esperando a outra pessoa...' : 'Esperando a criadora entrar...';
        await room.localParticipant.enableCameraAndMicrophone();
        const camPub = room.localParticipant.getTrackPublication(Track.Source.Camera);
        if (camPub && camPub.track) local.appendChild(camPub.track.attach());
    }).catch((e) => {
        aviso.textContent = 'Não deu pra entrar: ' + (e.message || e);
    });

    document.getElementById('mic').onclick = async function () {
        const on = room.localParticipant.isMicrophoneEnabled;
        await room.localParticipant.setMicrophoneEnabled(!on);
        this.classList.toggle('off', on);
    };
    document.getElementById('cam').onclick = async function () {
        const on = room.localParticipant.isCameraEnabled;
        await room.localParticipant.setCameraEnabled(!on);
        this.classList.toggle('off', on);
    };
    document.getElementById('sair').onclick = () => encerrar('Você saiu da chamada.');
})();
</script>
</body>
</html>
