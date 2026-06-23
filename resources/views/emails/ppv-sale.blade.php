<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova venda de Conteúdo Único</title>
    <style>
        body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1b1b18;
            background-color: #FDFDFC;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid rgba(26, 26, 0, 0.16);
        }
        .email-header {
            background-color: #1b1b18;
            color: #ffffff;
            padding: 30px 40px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 40px;
        }
        .email-body h2 {
            color: #1b1b18;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .email-body p {
            color: #706f6c;
            font-size: 16px;
            margin-bottom: 24px;
        }
        .sale-card {
            background-color: #F0FDF4;
            border: 1px solid #BBF7D0;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
        }
        .sale-card .amount {
            font-size: 32px;
            font-weight: 700;
            color: #16A34A;
            margin: 0 0 16px 0;
        }
        .sale-detail {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            padding: 6px 0;
            border-bottom: 1px solid #DCFCE7;
        }
        .sale-detail:last-child {
            border-bottom: none;
        }
        .sale-detail .label {
            color: #706f6c;
        }
        .sale-detail .value {
            color: #1b1b18;
            font-weight: 500;
        }
        .button-container {
            text-align: center;
            margin: 32px 0;
        }
        .cta-button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #f97316;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 16px;
        }
        .email-footer {
            padding: 24px 40px;
            background-color: #FDFDFC;
            border-top: 1px solid rgba(26, 26, 0, 0.16);
            text-align: center;
            font-size: 14px;
            color: #706f6c;
        }
        .email-footer p {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>💰 Nova venda realizada!</h1>
        </div>

        <div class="email-body">
            <h2>Olá, {{ $purchase->creator->name }}!</h2>

            <p>Ótima notícia! <strong>{{ $purchase->buyer->name }}</strong> acabou de comprar um dos seus Conteúdos Únicos.</p>

            <div class="sale-card">
                <p class="amount">+ R$ {{ number_format($purchase->creator_amount, 2, ',', '.') }}</p>

                <div class="sale-detail">
                    <span class="label">Valor pago pelo comprador</span>
                    <span class="value">R$ {{ number_format($purchase->amount_paid, 2, ',', '.') }}</span>
                </div>
                <div class="sale-detail">
                    <span class="label">Sua parte ({{ 100 - $purchase->platform_percentage }}%)</span>
                    <span class="value">R$ {{ number_format($purchase->creator_amount, 2, ',', '.') }}</span>
                </div>
                @if($purchase->post && $purchase->post->description)
                <div class="sale-detail">
                    <span class="label">Post</span>
                    <span class="value">{{ mb_strimwidth($purchase->post->description, 0, 60, '...') }}</span>
                </div>
                @endif
                <div class="sale-detail">
                    <span class="label">Data</span>
                    <span class="value">{{ $purchase->purchased_at->format('d/m/Y \à\s H:i') }}</span>
                </div>
            </div>

            <p>Acesse seu extrato para acompanhar todas as suas vendas.</p>

            <div class="button-container">
                <a href="{{ url('/extract') }}" class="cta-button">Ver extrato</a>
            </div>
        </div>

        <div class="email-footer">
            <p><strong>Pierfans</strong></p>
            <p>Este é um e-mail automático, por favor não responda.</p>
        </div>
    </div>
</body>
</html>
