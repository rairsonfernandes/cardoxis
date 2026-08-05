<?php
/**
 * CARDOXIS - Download de Arquivos
 * Version: 4.0.0 - Production Ready
 */

// ============================================
// LOG PARA DEBUG
// ============================================
function debugLog($message) {
    $logFile = __DIR__ . '/api/v1/logs/download.log';
    $timestamp = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

debugLog("=== DOWNLOAD REQUEST ===");
debugLog("GET params: " . json_encode($_GET));

// ============================================
// VALIDAR ARQUIVO
// ============================================
$file = isset($_GET['file']) ? $_GET['file'] : '';

if (empty($file)) {
    debugLog("Erro: Arquivo não especificado");
    http_response_code(400);
    die('Arquivo não especificado');
}

// Validar nome do arquivo
if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.(csv|json)$/', $file)) {
    debugLog("Erro: Nome de arquivo inválido: " . $file);
    http_response_code(403);
    die('Arquivo inválido');
}

// ============================================
// LISTA DE TODOS OS POSSÍVEIS CAMINHOS
// ============================================
$baseDir = __DIR__;
$possiblePaths = [
    // Caminho absoluto
    $baseDir . '/storage/exports/' . $file,
    $baseDir . '/api/v1/storage/exports/' . $file,
    $baseDir . '/../storage/exports/' . $file,
    
    // Caminho relativo
    './storage/exports/' . $file,
    '../storage/exports/' . $file,
    
    // Caminho Windows
    'C:/xampp/htdocs/cardoxis/storage/exports/' . $file,
    'C:/xampp/htdocs/storage/exports/' . $file,
    
    // Caminho alternativo
    dirname($baseDir) . '/storage/exports/' . $file,
    dirname(dirname($baseDir)) . '/storage/exports/' . $file,
];

$filepath = null;
foreach ($possiblePaths as $path) {
    debugLog("Tentando: " . $path);
    if (file_exists($path) && is_file($path)) {
        $filepath = $path;
        debugLog("✓ Arquivo encontrado em: " . $path);
        break;
    }
}

// ============================================
// SE NÃO ENCONTROU, VERIFICAR SE EXISTE NA PASTA DE EXPORTS
// ============================================
if (!$filepath) {
    // Tentar encontrar recursivamente
    $searchDirs = [
        $baseDir . '/storage/',
        $baseDir . '/../storage/',
        $baseDir . '/api/v1/storage/',
    ];
    
    foreach ($searchDirs as $dir) {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->getFilename() === $file) {
                    $filepath = $fileInfo->getPathname();
                    debugLog("✓ Arquivo encontrado recursivamente: " . $filepath);
                    break 2;
                }
            }
        }
    }
}

// ============================================
// SE AINDA NÃO ENCONTROU, LISTAR ARQUIVOS DISPONÍVEIS
// ============================================
if (!$filepath) {
    $availableFiles = [];
    $exportDirs = [
        $baseDir . '/storage/exports/',
        $baseDir . '/api/v1/storage/exports/',
        $baseDir . '/../storage/exports/',
    ];
    
    foreach ($exportDirs as $dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            if ($files) {
                foreach ($files as $f) {
                    if ($f !== '.' && $f !== '..' && pathinfo($f, PATHINFO_EXTENSION) === 'csv') {
                        $availableFiles[] = $f;
                    }
                }
            }
        }
    }
    
    debugLog("Erro: Arquivo não encontrado. Arquivos disponíveis: " . json_encode($availableFiles));
    
    http_response_code(404);
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Arquivo não encontrado</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f4f5f7; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #172B4D; }
            p { color: #42526E; }
            .btn { display: inline-block; padding: 10px 20px; background: #0052CC; color: white; text-decoration: none; border-radius: 6px; margin: 5px; }
            .btn:hover { background: #0047B3; }
            .file-list { text-align: left; background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .file-list li { padding: 5px 0; border-bottom: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>📄 Arquivo não encontrado</h1>
            <p>O arquivo <strong>' . htmlspecialchars($file) . '</strong> não foi encontrado.</p>
            
            ' . (!empty($availableFiles) ? '
            <div class="file-list">
                <h3>Arquivos disponíveis para download:</h3>
                <ul>
                    ' . implode('', array_map(function($f) {
                        return '<li><a href="/cardoxis/download.php?file=' . urlencode($f) . '">' . htmlspecialchars($f) . '</a></li>';
                    }, $availableFiles)) . '
                </ul>
            </div>
            ' : '
            <p style="color: #CC7000;">Nenhum arquivo CSV disponível. Tente exportar novamente.</p>
            ') . '
            
            <br>
            <a href="javascript:history.back()" class="btn">Voltar</a>
            <a href="/cardoxis/admin/logs" class="btn" style="background: #172B4D;">Ir para Logs</a>
        </div>
    </body>
    </html>';
    exit;
}

// ============================================
// LOG DE SUCESSO
// ============================================
debugLog("✓ Arquivo encontrado: " . $filepath);
debugLog("✓ Tamanho: " . filesize($filepath) . " bytes");

// ============================================
// HEADERS PARA DOWNLOAD
// ============================================
$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

switch ($extension) {
    case 'csv':
        $mimeType = 'text/csv';
        break;
    case 'json':
        $mimeType = 'application/json';
        break;
    default:
        $mimeType = 'application/octet-stream';
}

// Limpar buffers de saída
while (ob_get_level()) {
    ob_end_clean();
}

// Headers de download
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// ============================================
// ENVIAR ARQUIVO
// ============================================
readfile($filepath);

debugLog("✓ Download concluído: " . $file);
exit;