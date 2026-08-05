<?php
/**
 * CARDOXIS - Página em Construção RF
 */
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página em Construção | CARDOXIS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }
        .construction-container {
            background: white;
            border-radius: 24px;
            padding: 60px 48px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .construction-icon { font-size: 80px; color: #667eea; margin-bottom: 20px; }
        h1 { font-size: 28px; color: #1a1a2e; margin-bottom: 16px; }
        p { color: #666; margin-bottom: 32px; line-height: 1.6; }
        .btn-home {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; text-decoration: none; border-radius: 12px;
            font-weight: 600; transition: all 0.3s;
        }
        .btn-home:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(102,126,234,0.4); }
        .construction-status {
            margin-top: 20px;
            padding: 12px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #475569;
        }
        @media (max-width: 480px) {
            .construction-container { padding: 40px 24px; }
            .construction-icon { font-size: 60px; }
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="construction-container">
        <div class="construction-icon"><i class="fas fa-hard-hat"></i></div>
        <h1>Página em Construção</h1>
        <p>Estamos trabalhando para trazer esta página o mais breve possível.<br>Em breve estará disponível!</p>
        <a href="<?= url('dashboard') ?>" class="btn-home">
            <i class="fas fa-home"></i> Voltar ao dashboard
        </a>
        <div class="construction-status">
            <i class="fas fa-code"></i> Em desenvolvimento
        </div>
    </div>
</body>
</html>