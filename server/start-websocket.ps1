# CARDOXIS WebSocket Server - PowerShell Script
# Version: 1.0.0

Clear-Host

Write-Host ""
Write-Host "╔═══════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   🚀 CARDOXIS WebSocket Server                          ║" -ForegroundColor Cyan
Write-Host "╠═══════════════════════════════════════════════════════════╣" -ForegroundColor Cyan
Write-Host "║   📡 Iniciando servidor...                             ║" -ForegroundColor Cyan
Write-Host "║   🔗 ws://localhost:8080                               ║" -ForegroundColor Cyan
Write-Host "║   💡 Digite 'q' e Enter para sair                     ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Navegar para o diretório do servidor
Set-Location $PSScriptRoot

# Iniciar o servidor
php WebSocketServer.php

# Pausar ao finalizar
Read-Host "`nPressione Enter para sair..."