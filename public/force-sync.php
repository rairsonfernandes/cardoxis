<?php
/**
 * CARDOXIS - Forçar Sincronização de Sessão
 * @version 1.0.0
 */

// Iniciar sessão
session_start();

// Forçar dados manualmente
$_SESSION['user_id'] = 2;
$_SESSION['user_name'] = 'Administrador';
$_SESSION['user_email'] = 'admin@cardoxis.com';
$_SESSION['user_role'] = 'admin';
$_SESSION['authenticated'] = true;
$_SESSION['last_activity'] = time();

// Fechar e reabrir para garantir
session_write_close();
session_start();

echo "<h1>Sessão forçada criada com sucesso!</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Dados da sessão:\n";
print_r($_SESSION);
echo "</pre>";
echo "<br>";
echo "<a href='/cardoxis/dashboard'>Ir para o Dashboard</a><br>";
echo "<a href='/cardoxis/session-status'>Ver Status da Sessão</a><br>";
echo "<a href='/cardoxis/test-session'>Ver Teste de Sessão</a>";