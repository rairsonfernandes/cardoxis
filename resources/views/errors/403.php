<?php
/**
 * CARDOXIS - Página 403 (Acesso Negado) RF
 */
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acesso Negado | CARDOXIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
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
            background: white; color: #991b1b; padding: 12px 30px;
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
                    <div class="error-code"><i class="fas fa-ban"></i> 403</div>
                    <h1 class="error-title">Acesso Negado</h1>
                    <p class="error-message">Não tem permissão para aceder a esta página. Esta área é restrita.</p>
                    <a href="<?= url('') ?>" class="btn-home">
                        <i class="fas fa-home me-2"></i>Voltar ao Início
                    </a>
                    <div class="suggestions">
                        <a href="<?= url('dashboard') ?>"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                        <a href="<?= url('contact') ?>"><i class="fas fa-envelope me-1"></i> Contato</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>