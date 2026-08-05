<?php
/**
 * CARDOXIS - Configuração de Email
 * Version: 1.0.0 (Example Configuration)
 *
 * Exemplo de configuração para SMTP.
 * Substitua pelos seus próprios dados antes de usar em produção.
 *
 * @package CARDOXIS
 * @author Example Team
 * @license MIT
 * @copyright 2026 Example
 */

return [

    /**
     * Modo de envio:
     * 'gmail' para Gmail SMTP
     * 'mailtrap' para ambiente de desenvolvimento
     */
    'mode' => 'gmail',

    /**
     * Configuração Gmail SMTP (EXEMPLO)
     */
    'gmail' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@example.com',
        'password' => 'your-app-password',
        'from_email' => 'your-email@example.com',
        'from_name' => 'Example App',
        'encryption' => 'tls',
        'auth' => true,
        'timeout' => 30
    ],

    /**
     * Configuração Mailtrap (EXEMPLO)
     */
    'mailtrap' => [
        'host' => 'sandbox.smtp.mailtrap.io',
        'port' => 2525,
        'username' => 'your_mailtrap_username',
        'password' => 'your_mailtrap_password',
        'from_email' => 'noreply@example.com',
        'from_name' => 'Example App',
        'encryption' => 'tls',
        'auth' => true,
        'timeout' => 30
    ],

    /**
     * Configurações gerais
     */
    'general' => [
        'reply_to' => 'support@example.com',
        'charset' => 'UTF-8',
        'debug' => false
    ]
];