<?php
/**
 * CARDOXIS - Página 401 RF
 */
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Não Autorizado | CARDOXIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container { text-align: center; padding: 40px; }
        .error-code { font-size: 120px; font-weight: 800; color: rgba(255,255,255,0.2); text-shadow: 4px 4px 0 rgba(0,0,0,0.1); }
        .error-title { font-size: 32px; color: white; margin: 20px 0; }
        .error-message { color: rgba(255,255,255,0.8); margin-bottom: 30px; }
        .btn-home {
            background: white; color: #4f46e5; padding: 12px 30px;
            border-radius: 50px; font-weight: 600; text-decoration: none;
            transition: all 0.3s; display: inline-block;
        }
        .btn-home:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .suggestions { margin-top: 30px; color: rgba(255,255,255,0.6); }
        .suggestions a { color: white; text-decoration: none; margin: 0 10px; }
        .suggestions a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="error-container">
                    <div class="error-code"><i class="fas fa-lock"></i> 401</div>
                    <h1 class="error-title">Não Autorizado</h1>
                    <p class="error-message">É necessário fazer login para aceder a esta página.</p>
                    <a href="<?= url('login') ?>" class="btn-home">
                        <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
                    </a>
                    <div class="suggestions">
                        <a href="<?= url('') ?>"><i class="fas fa-home me-1"></i> Início</a>
                        <a href="<?= url('register') ?>"><i class="fas fa-user-plus me-1"></i> Registar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>