@echo off
cls
echo.
echo ============================================================
echo     🚀 CARDOXIS WebSocket Server
echo ============================================================
echo     📡 Servidor: ws://localhost:8080
echo     💡 Digite 'q' e Enter para sair
echo ============================================================
echo.

cd /d "%~dp0"
php WebSocketServer.php

echo.
echo Servidor encerrado.
pause