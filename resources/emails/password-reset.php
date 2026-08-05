<?php
/**
 * Template de Email para Recuperação de Senha RF
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - CARDOXIS</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f4f5f7;
            padding: 20px;
            margin: 0;
            color: #172B4D;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 48px 40px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo {
            font-size: 32px;
            font-weight: 800;
            color: #172B4D;
            letter-spacing: -1px;
        }
        .logo span {
            color: #0052CC;
        }
        .logo-sub {
            color: #6B778C;
            font-size: 14px;
            margin-top: 4px;
        }
        .divider {
            height: 2px;
            background: linear-gradient(to right, #DFE1E6, #0052CC, #DFE1E6);
            margin: 24px 0;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .message {
            color: #42526E;
            line-height: 1.7;
            font-size: 16px;
        }
        .pin-code {
            font-size: 48px;
            font-weight: 700;
            letter-spacing: 16px;
            text-align: center;
            padding: 24px;
            background: #F0F5FF;
            border-radius: 12px;
            margin: 24px 0;
            color: #0052CC;
            font-family: 'Courier New', monospace;
        }
        .info-box {
            background: #F4F5F7;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 16px 0;
            font-size: 14px;
            color: #42526E;
        }
        .warning-box {
            background: #FFF4E5;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 16px 0;
            font-size: 14px;
            color: #CC7000;
            border-left: 4px solid #FF8B00;
        }
        .footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #DFE1E6;
            font-size: 12px;
            color: #6B778C;
        }
        .footer a {
            color: #0052CC;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .btn {
            display: inline-block;
            background: #0052CC;
            color: #ffffff;
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
        }
        .btn:hover {
            background: #003D99;
        }
        @media (max-width: 480px) {
            .container { padding: 24px 16px; }
            .pin-code { font-size: 32px; letter-spacing: 12px; padding: 16px; }
            .logo { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">CARDOXIS<span>.io</span></div>
            <div class="logo-sub">Sistema de Gestão de Frotas</div>
        </div>
        
        <div class="divider"></div>
        
        <div class="greeting">Olá, <?php echo htmlspecialchars($name); ?>!</div>
        
        <div class="message">
            <p>Recebemos um pedido para redefinir a senha da sua conta no <strong>CARDOXIS</strong>.</p>
            <p>Utilize o código abaixo para continuar com o processo de recuperação:</p>
        </div>
        
        <div class="pin-code"><?php echo htmlspecialchars($pin); ?></div>
        
        <div class="info-box">
            <strong>⏱️ Prazo:</strong> Este código é válido por <strong><?php echo $expires_in ?? 15; ?> minutos</strong>.
        </div>
        
        <div class="warning-box">
            <strong>⚠️ Atenção:</strong> Se não solicitou esta recuperação, <strong>ignore este email</strong>. A sua conta permanece segura.
        </div>
        
        <div class="message">
            <p style="margin-top: 16px;">
                Precisa de ajuda? 
                <a href="mailto:suporte@cardoxis.io" style="color: #0052CC; font-weight: 600;">Contacte o suporte</a>
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> CARDOXIS. Todos os direitos reservados.</p>
            <p>
                Rua da Tecnologia, 123 - 1000-001 Lisboa, Portugal<br>
                <a href="mailto:suporte@cardoxis.io">suporte@cardoxis.io</a> | 
                <a href="#">Termos de Serviço</a> | 
                <a href="#">Política de Privacidade</a>
            </p>
        </div>
    </div>
</body>
</html>