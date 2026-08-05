<?php
/**
 * CARDOXIS - Página 503 (Service Unavailable) RF
 */
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Serviço Indisponível | CARDOXIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #475569 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container { text-align: center; padding: 40px; }
        .error-code { font-size: 120px; font-weight: 800; color: rgba(255,255,255,0.05); }
        .error-title { font-size: 32px; color: white; margin: 20px 0; }
        .error-message { color: rgba(255,255,255,0.6); margin-bottom: 30px; }
        .btn-home {
            background: white; color: #1e293b; padding: 12px 30px;
            border-radius: 50px; font-weight: 600; text-decoration: none;
            transition: all 0.3s; display: inline-block;
        }
        .btn-home:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
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
                    <div class="error-code"><i class="fas fa-server"></i> 503</div>
                    <h1 class="error-title">Serviço Indisponível</h1>
                    <p class="error-message">O servidor está temporariamente fora de serviço. Tente novamente mais tarde.</p>
                    <a href="<?= url('') ?>" class="btn-home">
                        <i class="fas fa-home me-2"></i>Voltar ao Início
                    </a>
                    <div class="suggestions">
                        <a href="<?= url('support') ?>"><i class="fas fa-headset me-1"></i> Suporte</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>