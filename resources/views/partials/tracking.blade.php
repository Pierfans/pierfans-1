{{-- Pixel do trafego pago (e futuro Google Ads), colado no admin em Configuracoes > Codigo de
     rastreamento, sem deploy. Nao existe layout compartilhado, cada view tem o proprio <head>,
     por isso o @include em cada uma (admin fica de fora, pra nao sujar o relatorio do anunciante).
     Cru de proposito: e script de terceiro, escapar quebraria; so admin grava. Nulo imprime nada. --}}
{!! \App\Models\PlatformSetting::getValue('tracking_head') !!}
