<?php
/**
 * CARDOXIS - Download de arquivos
 * @version 4.0.0 - Corrigido
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /cardoxis/login');
    exit;
}

// Verificar se o parâmetro file foi passado
$file = isset($_GET['file']) ? $_GET['file'] : '';
if (empty($file)) {
    die('Arquivo não especificado');
}

// Limpar o nome do arquivo
$file = basename($file);

// CAMINHO CORRETO - raiz do projeto
$projectRoot = dirname(__DIR__);
$exportDir = $projectRoot . '/storage/exports/';
$filepath = $exportDir . $file;

// Verificar se o arquivo existe
if (!file_exists($filepath)) {
    // Tentar também no caminho antigo (fallback)
    $oldPath = $projectRoot . '/api/storage/exports/' . $file;
    if (file_exists($oldPath)) {
        $filepath = $oldPath;
    } else {
        // Listar arquivos disponíveis para debug
        if (is_dir($exportDir)) {
            $files = glob($exportDir . '*.csv');
            $availableFiles = array_map('basename', $files);
            die('Arquivo não encontrado: ' . htmlspecialchars($file) . '<br><br>Arquivos disponíveis: ' . implode(', ', $availableFiles));
        } else {
            die('Pasta de exports não encontrada: ' . $exportDir);
        }
    }
}

// Headers para download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Limpar buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Enviar arquivo
readfile($filepath);
exit;