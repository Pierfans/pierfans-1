<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha</title>
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
        .reset-button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #1b1b18;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 16px;
            transition: background-color 0.2s;
        }
        .reset-button:hover {
            background-color: #000000;
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
        .token-info {
            background-color: #FDFDFC;
            border: 1px solid rgba(26, 26, 0, 0.16);
            border-radius: 4px;
            padding: 16px;
            margin: 24px 0;
            font-size: 14px;
            color: #706f6c;
        }
        .warning {
            background-color: #fff2f2;
            border-left: 4px solid #F53003;
            padding: 16px;
            margin: 24px 0;
            font-size: 14px;
            color: #1b1b18;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Recuperação de Senha</h1>
        </div>

        <div class="email-body">
            <h2>Olá, {{ $user->name }}!</h2>

            <p>Recebemos uma solicitação para redefinir a senha da sua conta.</p>

            <p>Clique no botão abaixo para criar uma nova senha:</p>

            <div class="button-container">
                <a href="{{ $resetUrl }}" class="reset-button">Redefinir Senha</a>
            </div>

            <p>Ou copie e cole o link abaixo no seu navegador:</p>

            <div class="token-info">
                <a href="{{ $resetUrl }}" style="color: #f53003; word-break: break-all;">{{ $resetUrl }}</a>
            </div>

            <div class="warning">
                <strong>⚠️ Importante:</strong> Este link expira em 60 minutos. Se você não solicitou esta recuperação de senha, ignore este e-mail.
            </div>

            <p style="margin-top: 32px; font-size: 14px; color: #706f6c;">
                Se você não solicitou esta recuperação de senha, nenhuma ação é necessária.
            </p>
        </div>

        <div class="email-footer">
            <p>Pierfans</strong></p>
            <p>Este é um e-mail automático, por favor não responda.</p>
        </div>
    </div>
</body>
</html>

