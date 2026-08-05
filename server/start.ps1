# CARDOXIS WebSocket Server - Startup Script
Clear-Host

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "    🚀 CARDOXIS WebSocket Server" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "    📡 Servidor: ws://localhost:8080" -ForegroundColor Green
Write-Host "    💡 Digite 'q' e Enter para sair" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Ir para o diretório do script
Set-Location $PSScriptRoot

# Iniciar servidor
php WebSocketServer.php

# Pausar ao finalizar
Read-Host "`nPressione Enter para sair..."