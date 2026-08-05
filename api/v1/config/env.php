<?php
/**
 * CARDOXIS - Carregador de Variáveis de Ambiente RF
 */

// Carregar arquivo .env da raiz do projeto
$envFile = __DIR__ . '/../../../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parsear linha
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remover aspas
            $value = trim($value, '"');
            $value = trim($value, "'");
            
            // Definir variável de ambiente
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}