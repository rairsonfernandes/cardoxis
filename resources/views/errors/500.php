<?php
/**
 * CARDOXIS - Página 500 (Erro Interno) RF
 */
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Erro Interno | CARDOXIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container { text-align: center; padding: 40px; }
        .error-code { font-size: 120px; font-weight: 800; color: rgba(255,255,255,0.05); }
        .error-title { font-size: 32px; color: white; margin: 20px 0; }
        .error-message { color: rgba(255,255,255,0.6); margin-bottom: 30px; }
        .btn-reload {
            background: #2563eb; color: white; padding: 12px 30px;
            border-radius: 50px; font-weight: 600; text-decoration: none;
            transition: all 0.3s; display: inline-block; border: none; cursor: pointer;
        }
        .btn-reload:hover { background: #1d4ed8; transform: translateY(-3px); box-shadow: 0 10px 30px rgba(37,99,235,0.3); }
        .suggestions { margin-top: 30px; color: rgba(255,255,255,0.4); }
        .suggestions a { color: rgba(255,255,255,0.6); text-decoration: none; margin: 0 10px; }
        .suggestions a:hover { color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="error-container">
                    <div class="error-code"><i class="fas fa-exclamation-triangle"></i> 500</div>
                    <h1 class="error-title">Ops! Algo correu mal</h1>
                    <p class="error-message">Os nossos engenheiros já foram notificados. Tente novamente dentro de alguns minutos.</p>
                    
                    <button onclick="location.reload()" class="btn-reload">
                        <i class="fas fa-sync-alt me-2"></i>Tentar Novamente
                    </button>
                    
                    <div class="suggestions">
                        <a href="<?= url('') ?>"><i class="fas fa-home me-1"></i> Início</a>
                        <a href="<?= url('support') ?>"><i class="fas fa-headset me-1"></i> Suporte</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>