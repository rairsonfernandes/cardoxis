<?php
/**
 * CARDOXIS - Teste Rápido de Alertas
 * Acesse: http://localhost/cardoxis/api/v1/cron/test_alerts.php
 */

echo "<h1>Teste de Alertas Automáticos</h1>";

// Verificar se a classe AlertController existe
if (!class_exists('AlertController')) {
    require_once __DIR__ . '/../controllers/AlertController.php';
}

try {
    $db = Database::getInstance();
    
    echo "<h2>📊 Verificando dados existentes:</h2>";
    
    // Verificar documentos
    $docs = $db->fetchAll("SELECT id, title, expiry_date FROM documents WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    echo "<p>📄 Documentos a expirar: " . count($docs) . "</p>";
    
    // Verificar manutenções
    $maints = $db->fetchAll("
        SELECT m.id, m.title, m.scheduled_date, v.plate 
        FROM maintenances m 
        JOIN vehicles v ON m.vehicle_id = v.id 
        WHERE m.scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    echo "<p>🔧 Manutenções próximas: " . count($maints) . "</p>";
    
    // Verificar seguros
    $insurances = $db->fetchAll("SELECT id, policy_number, end_date FROM insurances WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    echo "<p>🛡️ Seguros a expirar: " . count($insurances) . "</p>";
    
    // Verificar multas
    $fines = $db->fetchAll("SELECT id, fine_number, due_date FROM fines WHERE due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    echo "<p>💰 Multas a vencer: " . count($fines) . "</p>";
    
    // Verificar licenças
    $licenses = $db->fetchAll("SELECT id, name, license_expiry_date FROM drivers WHERE license_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    echo "<p>📋 Licenças a expirar: " . count($licenses) . "</p>";
    
    echo "<hr>";
    
    $alertController = new AlertController();
    
    echo "<h2>1. Verificando documentos a expirar...</h2>";
    $docsCreated = $alertController->checkDocumentExpirations();
    echo "<p>Documentos verificados: " . ($docsCreated !== false ? $docsCreated : '0') . "</p>";
    
    echo "<h2>2. Verificando manutenções próximas...</h2>";
    $maintsCreated = $alertController->checkMaintenanceUpcoming();
    echo "<p>Manutenções verificadas: " . ($maintsCreated !== false ? $maintsCreated : '0') . "</p>";
    
    echo "<h2>3. Verificando seguros a expirar...</h2>";
    $insurancesCreated = $alertController->checkInsuranceExpirations();
    echo "<p>Seguros verificados: " . ($insurancesCreated !== false ? $insurancesCreated : '0') . "</p>";
    
    echo "<h2>4. Verificando multas a vencer...</h2>";
    $finesCreated = $alertController->checkFinesDue();
    echo "<p>Multas verificadas: " . ($finesCreated !== false ? $finesCreated : '0') . "</p>";
    
    echo "<h2>5. Verificando licenças de motoristas...</h2>";
    $licensesCreated = $alertController->checkDriverLicenses();
    echo "<p>Licenças verificadas: " . ($licensesCreated !== false ? $licensesCreated : '0') . "</p>";
    
    $total = $docsCreated + $maintsCreated + $insurancesCreated + $finesCreated + $licensesCreated;
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Total de alertas criados: " . $total . "</h2>";
    
    // Mostrar alertas criados
    if ($total > 0) {
        echo "<h3>📋 Alertas criados:</h3>";
        $alerts = $db->fetchAll("SELECT id, title, type, severity, created_at FROM alerts ORDER BY id DESC LIMIT 10");
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Título</th><th>Tipo</th><th>Severidade</th><th>Data</th></tr>";
        foreach ($alerts as $alert) {
            echo "<tr>";
            echo "<td>{$alert['id']}</td>";
            echo "<td>" . htmlspecialchars($alert['title']) . "</td>";
            echo "<td>{$alert['type']}</td>";
            echo "<td>{$alert['severity']}</td>";
            echo "<td>{$alert['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}