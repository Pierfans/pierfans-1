<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $content['heading'] }}</title>
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
            <h1>{{ $content['emoji'] }} {{ $content['heading'] }}</h1>
        </div>

        <div class="email-body">
            <h2>Olá, {{ $user->name }}!</h2>

            @foreach($content['lines'] as $line)
                <p>{{ $line }}</p>
            @endforeach

            @if($content['cta'])
            <div class="button-container">
                <a href="{{ $content['cta']['url'] }}" class="cta-button">{{ $content['cta']['label'] }}</a>
            </div>
            @endif
        </div>

        <div class="email-footer">
            <p><strong>Pierfans</strong></p>
            <p>Este é um e-mail automático, por favor não responda.</p>
        </div>
    </div>
</body>
</html>
