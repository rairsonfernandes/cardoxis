/**
 * CARDOXIS - WebSocket Server
 * Version: 1.0.0 (Production Ready)
 */

const WebSocket = require('ws');
const http = require('http');
const fs = require('fs');
const path = require('path');

// Configurações
const PORT = 8080;
const WS_PATH = '/ws/live-map';

// Armazenamento de conexões
const clients = new Map();
const vehiclePositions = new Map();
const eventLogs = [];

// Criar servidor HTTP
const server = http.createServer((req, res) => {
    // Health check
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            status: 'ok',
            clients: clients.size,
            timestamp: new Date().toISOString()
        }));
        return;
    }
    
    // Status do servidor
    if (req.url === '/status') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            status: 'running',
            clients: clients.size,
            vehicles: vehiclePositions.size,
            events: eventLogs.length,
            uptime: process.uptime()
        }));
        return;
    }
    
    res.writeHead(404);
    res.end();
});

// Criar WebSocket Server
const wss = new WebSocket.Server({
    server,
    path: WS_PATH
});

// Log de eventos
function logEvent(message, type = 'info') {
    const event = {
        id: eventLogs.length + 1,
        type: type,
        message: message,
        timestamp: new Date().toISOString()
    };
    eventLogs.push(event);
    
    // Manter apenas últimos 100 eventos
    if (eventLogs.length > 100) {
        eventLogs.shift();
    }
    
    // Broadcast para todos os clientes
    broadcast({
        type: 'event',
        data: event
    });
    
    console.log(`[${event.timestamp}] ${type.toUpperCase()}: ${message}`);
}

// Broadcast para todos os clientes
function broadcast(data) {
    const message = JSON.stringify(data);
    clients.forEach((client, id) => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message);
        }
    });
}

// Enviar para um cliente específico
function sendToClient(clientId, data) {
    const client = clients.get(clientId);
    if (client && client.readyState === WebSocket.OPEN) {
        client.send(JSON.stringify(data));
    }
}

// Gerar posição aleatória
function generateRandomPosition(baseLat = 38.7223, baseLng = -9.1393) {
    return {
        latitude: baseLat + (Math.random() - 0.5) * 0.01,
        longitude: baseLng + (Math.random() - 0.5) * 0.01,
        speed: Math.floor(Math.random() * 80),
        heading: Math.floor(Math.random() * 360)
    };
}

// Simular movimento de veículos
function simulateVehicleMovements() {
    vehiclePositions.forEach((position, vehicleId) => {
        // Atualizar posição com movimento aleatório
        const newPos = generateRandomPosition(
            position.latitude + (Math.random() - 0.5) * 0.001,
            position.longitude + (Math.random() - 0.5) * 0.001
        );
        
        vehiclePositions.set(vehicleId, {
            ...position,
            ...newPos,
            last_update: new Date().toISOString()
        });
        
        // Notificar todos os clientes
        broadcast({
            type: 'vehicle.position.updated',
            vehicle_id: vehicleId,
            latitude: newPos.latitude,
            longitude: newPos.longitude,
            speed: newPos.speed,
            heading: newPos.heading,
            timestamp: new Date().toISOString()
        });
    });
}

// Inicializar veículos mock
function initializeMockVehicles() {
    const vehicles = [
        { id: 1, plate: 'AB-12-34', brand: 'Mercedes-Benz', model: 'Actros' },
        { id: 2, plate: 'CD-56-78', brand: 'Volvo', model: 'FH 540' },
        { id: 3, plate: 'EF-90-12', brand: 'Scania', model: 'R 450' },
        { id: 4, plate: 'GH-34-56', brand: 'Renault', model: 'Master' },
        { id: 5, plate: 'IJ-78-90', brand: 'Iveco', model: 'Daily' }
    ];
    
    vehicles.forEach(vehicle => {
        const position = generateRandomPosition();
        vehiclePositions.set(vehicle.id, {
            ...vehicle,
            ...position,
            status: ['active', 'in_route', 'idle'][Math.floor(Math.random() * 3)],
            last_update: new Date().toISOString()
        });
    });
    
    logEvent(`${vehicles.length} veículos inicializados`, 'info');
}

// WebSocket Connection Handler
wss.on('connection', (ws, req) => {
    const clientId = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    const clientIP = req.socket.remoteAddress;
    
    clients.set(clientId, ws);
    
    logEvent(`Cliente conectado: ${clientId} (${clientIP})`, 'info');
    
    // Enviar estado inicial
    ws.send(JSON.stringify({
        type: 'connection.established',
        client_id: clientId,
        timestamp: new Date().toISOString(),
        vehicles: Array.from(vehiclePositions.values()),
        events: eventLogs.slice(-20)
    }));
    
    // Enviar confirmação de conexão
    ws.send(JSON.stringify({
        type: 'connection.confirmed',
        message: 'Conectado ao servidor WebSocket',
        timestamp: new Date().toISOString()
    }));
    
    // Handle messages from client
    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            console.log(`[${clientId}] Mensagem recebida:`, data);
            
            switch (data.type) {
                case 'ping':
                    ws.send(JSON.stringify({
                        type: 'pong',
                        timestamp: new Date().toISOString()
                    }));
                    break;
                    
                case 'subscribe.vehicle':
                    // Assinar atualizações de um veículo específico
                    const vehicleId = data.vehicle_id;
                    if (vehicleId && vehiclePositions.has(vehicleId)) {
                        ws.send(JSON.stringify({
                            type: 'vehicle.subscribed',
                            vehicle_id: vehicleId,
                            position: vehiclePositions.get(vehicleId)
                        }));
                    }
                    break;
                    
                case 'send.message':
                    // Enviar mensagem para motorista
                    const driverId = data.driver_id;
                    const messageText = data.message;
                    logEvent(`Mensagem enviada para motorista ${driverId}: ${messageText}`, 'info');
                    
                    // Broadcast da mensagem
                    broadcast({
                        type: 'message.sent',
                        driver_id: driverId,
                        message: messageText,
                        sender: data.sender || 'Sistema',
                        timestamp: new Date().toISOString()
                    });
                    
                    ws.send(JSON.stringify({
                        type: 'message.confirmed',
                        message: 'Mensagem enviada com sucesso',
                        timestamp: new Date().toISOString()
                    }));
                    break;
                    
                case 'alert.acknowledge':
                    // Reconhecer alerta
                    const alertId = data.alert_id;
                    logEvent(`Alerta ${alertId} reconhecido`, 'warning');
                    
                    broadcast({
                        type: 'alert.acknowledged',
                        alert_id: alertId,
                        acknowledged_by: clientId,
                        timestamp: new Date().toISOString()
                    });
                    break;
                    
                default:
                    console.log(`[${clientId}] Tipo de mensagem desconhecido:`, data.type);
            }
        } catch (error) {
            console.error(`[${clientId}] Erro ao processar mensagem:`, error);
        }
    });
    
    // Handle disconnection
    ws.on('close', () => {
        clients.delete(clientId);
        logEvent(`Cliente desconectado: ${clientId}`, 'info');
    });
    
    // Handle errors
    ws.on('error', (error) => {
        console.error(`[${clientId}] Erro WebSocket:`, error);
        clients.delete(clientId);
    });
});

// Simular eventos periódicos
setInterval(() => {
    // Atualizar posições dos veículos
    simulateVehicleMovements();
    
    // Gerar eventos aleatórios
    const eventTypes = [
        'delivery_completed',
        'delivery_assigned',
        'route_started',
        'alert',
        'vehicle_online',
        'vehicle_offline'
    ];
    
    if (Math.random() < 0.3) { // 30% de chance de gerar um evento
        const type = eventTypes[Math.floor(Math.random() * eventTypes.length)];
        const messages = {
            'delivery_completed': 'Entrega #' + Math.floor(Math.random() * 1000) + ' concluída',
            'delivery_assigned': 'Entrega #' + Math.floor(Math.random() * 1000) + ' atribuída',
            'route_started': 'Rota #' + Math.floor(Math.random() * 100) + ' iniciada',
            'alert': 'Alerta: Veículo ' + ['ABC-1234', 'DEF-5678', 'GHI-9012'][Math.floor(Math.random() * 3)] + ' excedeu velocidade',
            'vehicle_online': 'Veículo ' + ['ABC-1234', 'DEF-5678', 'GHI-9012'][Math.floor(Math.random() * 3)] + ' ficou online',
            'vehicle_offline': 'Veículo ' + ['ABC-1234', 'DEF-5678', 'GHI-9012'][Math.floor(Math.random() * 3)] + ' ficou offline'
        };
        
        logEvent(messages[type], type === 'alert' ? 'warning' : 'info');
    }
}, 3000);

// Iniciar servidor
server.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 CARDOXIS WebSocket Server`);
    console.log(`📡 Servidor rodando em ws://localhost:${PORT}${WS_PATH}`);
    console.log(`🔗 Health check: http://localhost:${PORT}/health`);
    console.log(`📊 Status: http://localhost:${PORT}/status`);
    console.log(`🕒 Iniciado em: ${new Date().toISOString()}`);
    console.log(`📦 Veículos mock: 5`);
    
    // Inicializar veículos mock
    initializeMockVehicles();
});

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('\n🛑 Servidor WebSocket desligando...');
    wss.close(() => {
        console.log('✅ WebSocket fechado');
        server.close(() => {
            console.log('✅ Servidor HTTP fechado');
            process.exit(0);
        });
    });
});

process.on('SIGTERM', () => {
    console.log('\n🛑 Servidor WebSocket terminando...');
    wss.close(() => {
        console.log('✅ WebSocket fechado');
        server.close(() => {
            console.log('✅ Servidor HTTP fechado');
            process.exit(0);
        });
    });
});

// Exportar para uso em outros módulos
module.exports = { wss, server, clients, vehiclePositions, broadcast, logEvent };