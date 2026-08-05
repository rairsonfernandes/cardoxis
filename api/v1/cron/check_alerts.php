#!/usr/bin/env php
<?php
/**
 * CARDOXIS - Script de Verificação Automática de Alertas RF 
 */

// ============================================
// CONFIGURAÇÕES
// ============================================
define('ROOT_PATH', dirname(__DIR__, 3));
define('LOG_FILE', ROOT_PATH . '/storage/logs/alerts_cron.log');

// ============================================
// CARREGAR DEPENDÊNCIAS
// ============================================
require_once ROOT_PATH . '/api/v1/config/Database.php';
require_once ROOT_PATH . '/api/v1/controllers/AlertController.php';

// ============================================
// FUNÇÕES
// ============================================
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$type}] {$message}\n";
    echo $logEntry;
    
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

function getCompanyIdFromArgs($argv) {
    if (isset($argv[1]) && is_numeric($argv[1])) {
        return (int)$argv[1];
    }
    return null;
}

function getActiveCompanies() {
    try {
        $db = Database::getInstance();
        $companies = $db->fetchAll("SELECT id, name FROM companies WHERE status = 'active'");
        return $companies;
    } catch (Exception $e) {
        logMessage("Erro ao buscar empresas: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

// ============================================
// EXECUÇÃO PRINCIPAL
// ============================================
logMessage("========================================");
logMessage("INICIANDO VERIFICAÇÃO DE ALERTAS");
logMessage("========================================");

try {
    $alertController = new AlertController();
    $companyId = getCompanyIdFromArgs($argv);
    
    if ($companyId) {
        logMessage("Verificando empresa ID: {$companyId}");
        $total = $alertController->runAllChecks($companyId);
        logMessage("✅ Alertas criados para empresa {$companyId}: {$total}");
    } else {
        logMessage("Verificando todas as empresas ativas...");
        $companies = getActiveCompanies();
        $totalGlobal = 0;
        
        if (empty($companies)) {
            logMessage("⚠️ Nenhuma empresa ativa encontrada");
        }
        
        foreach ($companies as $company) {
            logMessage("Verificando empresa: {$company['name']} (ID: {$company['id']})");
            try {
                $total = $alertController->runAllChecks($company['id']);
                $totalGlobal += $total;
                logMessage("  ✅ Alertas criados: {$total}");
                
                // Detalhar por tipo
                $db = Database::getInstance();
                $alerts = $db->fetchAll("
                    SELECT type, COUNT(*) as count 
                    FROM alerts 
                    WHERE company_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    GROUP BY type
                ", [$company['id']]);
                
                foreach ($alerts as $alert) {
                    logMessage("    📌 {$alert['type']}: {$alert['count']}");
                }
            } catch (Exception $e) {
                logMessage("  ❌ Erro na empresa {$company['id']}: " . $e->getMessage(), 'ERROR');
            }
        }
        
        logMessage("========================================");
        logMessage("✅ TOTAL DE ALERTAS CRIADOS: {$totalGlobal}");
    }
    
    logMessage("========================================");
    logMessage("VERIFICAÇÃO CONCLUÍDA COM SUCESSO!");
    logMessage("========================================");
    
} catch (Exception $e) {
    logMessage("❌ ERRO: " . $e->getMessage(), 'ERROR');
    logMessage("❌ TRACE: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}

exit(0);