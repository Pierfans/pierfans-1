{{-- Pixel do trafego pago (e futuro Google Ads), colado no admin em Configuracoes > Codigo de
     rastreamento, sem deploy. Nao existe layout compartilhado, cada view tem o proprio <head>,
     por isso o @include em cada uma (admin fica de fora, pra nao sujar o relatorio do anunciante).
     Cru de proposito: e script de terceiro, escapar quebraria; so admin grava. Nulo imprime nada. --}}
{!! \App\Models\PlatformSetting::getValue('tracking_head') !!}

{{-- Conversao do TrafficJunky (bento 18/09). O controller marca 'tj_pixel' na sessao (cadastro ou
     venda) e a primeira pagina que abrir dispara e apaga: o cadastro redireciona pra 5 lugares
     diferentes e todos incluem este partial. <img> criado por JS porque aqui e <head>. --}}
@php($tj = session()->pull('tj_pixel'))
@if($tj)
<meta http-equiv="Delegate-CH" content="sec-ch-ua https://ads.trafficjunky.net; sec-ch-ua-arch https://ads.trafficjunky.net; sec-ch-ua-full-version-list https://ads.trafficjunky.net; sec-ch-ua-mobile https://ads.trafficjunky.net; sec-ch-ua-model https://ads.trafficjunky.net; sec-ch-ua-platform https://ads.trafficjunky.net; sec-ch-ua-platform-version https://ads.trafficjunky.net;">
@php($tjA = $tj['tipo'] === 'cadastro' ? '1000588171' : '1000588181')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var i = new Image(1, 1);
    i.id = '{{ $tjA }}_cpa_testing';
    i.border = 0;
    i.src = 'https://ads.trafficjunky.net/ct?a={{ $tjA }}&member_id=1009194501&cb=' + Date.now() + '&cti={{ $tj['id'] }}&ctv=&ctd={{ $tj['tipo'] }}';
    document.body.appendChild(i);
});
</script>
@endif
