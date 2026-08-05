<?php
/**
 * CARDOXIS - Email Configuration RF 
 */

return [
    // Método padrão: 'smtp', 'mail', 'sendmail'
    'default' => 'smtp',
    
    // Configuração SMTP (recomendado para Gmail)
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'seu-email@gmail.com', // ALTERE AQUI
        'password' => 'sua-senha-de-app', // ALTERE AQUI (senha de aplicação)
        'encryption' => 'tls',
        'auth' => true,
        'timeout' => 30,
        'debug' => true
    ],
    
    // Configuração Mailtrap (para testes)
    'mailtrap' => [
        'host' => 'smtp.mailtrap.io',
        'port' => 2525,
        'username' => 'seu-usuario-mailtrap',
        'password' => 'sua-senha-mailtrap',
        'encryption' => 'tls',
        'auth' => true,
        'timeout' => 30,
        'debug' => true
    ],
    
    // Configuração do remetente
    'mail' => [
        'from' => [
            'email' => 'noreply@cardoxis.io',
            'name' => 'CARDOXIS'
        ],
        'reply_to' => 'suporte@cardoxis.io'
    ],
    
    // Templates de email
    'templates' => [
        'password_reset' => [
            'subject' => 'Código de Recuperação - CARDOXIS',
            'view' => 'emails/password-reset.php'
        ]
    ]
];