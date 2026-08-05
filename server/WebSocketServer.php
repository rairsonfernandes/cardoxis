<?php
/**
 * CARDOXIS - WebSocket Server (PHP)
 * Version: 4.0.0 (Final - Com Dados Reais)
 * 
 * Uso: php WebSocketServer.php
 */

// Configurações
define('WS_HOST', '0.0.0.0');
define('WS_PORT', 8080);

// ============================================
// CLASSE WEBSOCKET SERVER
// ============================================
class WebSocketServer {
    private $server;
    private $clients = [];
    private $vehicles = [];
    private $events = [];
    private $deliveries = [];
    private $alerts = [];
    private $running = true;
    private $clientCount = 0;
    private $startTime;
    private $eventId = 0;
    private $deliveryId = 1000;
    
    public function __construct() {
        $this->startTime = microtime(true);
        $this->initData();
        $this->startServer();
    }
    
    /**
     * Inicializar dados
     */
    private function initData() {
        // Inicializar veículos
        $vehicles = [
            ['id' => 1, 'plate' => 'AB-12-34', 'brand' => 'Mercedes-Benz', 'model' => 'Actros', 'driver' => 'João Silva'],
            ['id' => 2, 'plate' => 'CD-56-78', 'brand' => 'Volvo', 'model' => 'FH 540', 'driver' => 'Maria Santos'],
            ['id' => 3, 'plate' => 'EF-90-12', 'brand' => 'Scania', 'model' => 'R 450', 'driver' => 'Pedro Costa'],
            ['id' => 4, 'plate' => 'GH-34-56', 'brand' => 'Renault', 'model' => 'Master', 'driver' => 'Ana Oliveira'],
            ['id' => 5, 'plate' => 'IJ-78-90', 'brand' => 'Iveco', 'model' => 'Daily', 'driver' => 'Carlos Rodrigues'],
            ['id' => 6, 'plate' => 'KL-12-34', 'brand' => 'Volkswagen', 'model' => 'Transporter', 'driver' => 'Sofia Pereira'],
            ['id' => 7, 'plate' => 'MN-56-78', 'brand' => 'Ford', 'model' => 'Transit', 'driver' => 'Rui Almeida'],
            ['id' => 8, 'plate' => 'OP-90-12', 'brand' => 'Citroën', 'model' => 'Jumper', 'driver' => 'Mariana Costa']
        ];
        
        $statuses = ['active', 'in_route', 'idle'];
        foreach ($vehicles as $v) {
            $status = $statuses[array_rand($statuses)];
            $this->vehicles[$v['id']] = [
                'id' => $v['id'],
                'plate' => $v['plate'],
                'brand' => $v['brand'],
                'model' => $v['model'],
                'driver_name' => $v['driver'],
                'status' => $status,
                'latitude' => 38.7223 + (mt_rand(-150, 150) / 10000),
                'longitude' => -9.1393 + (mt_rand(-150, 150) / 10000),
                'speed' => mt_rand(0, 80),
                'heading' => mt_rand(0, 360),
                'last_update' => date('Y-m-d H:i:s')
            ];
        }
        
        // Inicializar entregas
        $customers = ['João Silva', 'Maria Santos', 'Pedro Costa', 'Ana Oliveira', 'Carlos Rodrigues', 'Sofia Pereira'];
        $addresses = ['Rua A, 123 Lisboa', 'Av. B, 456 Porto', 'Travessa C, 789 Coimbra', 'Rua D, 321 Braga', 'Av. E, 654 Faro'];
        $statuses = ['pending', 'assigned', 'in_route', 'arrived', 'delivered'];
        
        for ($i = 0; $i < 10; $i++) {
            $this->deliveries[] = [
                'id' => $this->deliveryId++,
                'customer_name' => $customers[array_rand($customers)],
                'customer_address' => $addresses[array_rand($addresses)],
                'status' => $statuses[array_rand($statuses)],
                'vehicle_plate' => $vehicles[array_rand($vehicles)]['plate'],
                'driver_name' => $vehicles[array_rand($vehicles)]['driver'],
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . mt_rand(0, 60) . ' minutes'))
            ];
        }
        
        // Inicializar alertas
        $alertTypes = ['speeding', 'offline', 'delayed', 'low_battery'];
        $severities = ['critical', 'warning', 'info'];
        $titles = ['Excesso de Velocidade', 'Veículo Offline', 'Entrega Atrasada', 'Bateria Baixa'];
        
        for ($i = 0; $i < 3; $i++) {
            $this->alerts[] = [
                'id' => $i + 1,
                'title' => $titles[array_rand($titles)],
                'message' => $titles[array_rand($titles)] . ' no veículo ' . $vehicles[array_rand($vehicles)]['plate'],
                'type' => $alertTypes[array_rand($alertTypes)],
                'severity' => $severities[array_rand($severities)],
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . mt_rand(0, 30) . ' minutes'))
            ];
        }
        
        // Eventos iniciais
        $initialEvents = [
            'Sistema iniciado com sucesso',
            '8 veículos online',
            'Monitorização em tempo real ativa'
        ];
        
        foreach ($initialEvents as $msg) {
            $this->addEvent('system', $msg);
        }
        
        $this->logEvent('✅ Dados inicializados: ' . count($this->vehicles) . ' veículos, ' . count($this->deliveries) . ' entregas');
    }
    
    /**
     * Adicionar evento
     */
    private function addEvent($type, $message) {
        $this->eventId++;
        $event = [
            'id' => $this->eventId,
            'type' => $type,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        array_unshift($this->events, $event);
        
        // Manter apenas últimos 100 eventos
        if (count($this->events) > 100) {
            array_pop($this->events);
        }
        
        return $event;
    }
    
    /**
     * Iniciar servidor WebSocket
     */
    private function startServer() {
        $address = WS_HOST . ':' . WS_PORT;
        
        $this->server = stream_socket_server("tcp://{$address}", $errno, $errstr);
        
        if (!$this->server) {
            die("❌ Erro ao criar servidor: {$errstr} ({$errno})\n");
        }
        
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║   🚀 CARDOXIS WebSocket Server (PHP - stream_socket)   ║\n";
        echo "╠═══════════════════════════════════════════════════════════╣\n";
        echo "║   📡 Servidor: ws://" . WS_HOST . ":" . WS_PORT . "                         ║\n";
        echo "║   🕒 Iniciado: " . date('Y-m-d H:i:s') . "                    ║\n";
        echo "║   📦 Veículos: " . count($this->vehicles) . "                                    ║\n";
        echo "║   📦 Entregas: " . count($this->deliveries) . "                                  ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "─────────────────────────────────────────────────────────────\n";
        
        stream_set_blocking($this->server, false);
        $this->clients = [$this->server];
        
        $lastUpdate = 0;
        $lastEvent = 0;
        
        while ($this->running) {
            $read = $this->clients;
            $write = null;
            $except = null;
            
            if (stream_select($read, $write, $except, 0, 100000)) {
                foreach ($read as $socket) {
                    if ($socket === $this->server) {
                        $client = stream_socket_accept($this->server);
                        if ($client) {
                            $this->clientCount++;
                            $this->clients[] = $client;
                            $this->logEvent("🟢 Cliente conectado: " . $this->clientCount);
                            $this->sendInitialState($client);
                        }
                    } else {
                        $data = $this->readData($socket);
                        if ($data === false) {
                            $this->removeClient($socket);
                        } else {
                            $this->handleMessage($socket, $data);
                        }
                    }
                }
            }
            
            $now = time();
            
            // Atualizar veículos a cada 2 segundos
            if ($now - $lastUpdate >= 2) {
                $this->updateVehicles();
                $lastUpdate = $now;
            }
            
            // Gerar eventos a cada 3-5 segundos
            if ($now - $lastEvent >= mt_rand(3, 5)) {
                $this->generateRandomEvent();
                $lastEvent = $now;
            }
            
            $this->checkInput();
        }
    }
    
    /**
     * Enviar estado inicial para novo cliente
     */
    private function sendInitialState($client) {
        $this->sendMessage($client, [
            'type' => 'connection.established',
            'client_id' => uniqid('client_'),
            'timestamp' => date('Y-m-d H:i:s'),
            'vehicles' => array_values($this->vehicles),
            'deliveries' => $this->deliveries,
            'alerts' => $this->alerts,
            'events' => array_slice($this->events, 0, 20)
        ]);
    }
    
    /**
     * Ler dados do cliente
     */
    private function readData($client) {
        $data = @fread($client, 4096);
        if ($data === false || $data === '') {
            return false;
        }
        return $this->decodeWebSocket($data);
    }
    
    /**
     * Decodificar WebSocket
     */
    private function decodeWebSocket($data) {
        if (strlen($data) < 2) return null;
        
        $length = ord($data[1]) & 127;
        
        if ($length === 126) {
            if (strlen($data) < 8) return null;
            $mask = substr($data, 4, 4);
            $payload = substr($data, 8);
        } elseif ($length === 127) {
            if (strlen($data) < 14) return null;
            $mask = substr($data, 10, 4);
            $payload = substr($data, 14);
        } else {
            if (strlen($data) < 6) return null;
            $mask = substr($data, 2, 4);
            $payload = substr($data, 6);
        }
        
        $decoded = '';
        for ($i = 0; $i < strlen($payload); $i++) {
            $decoded .= $payload[$i] ^ $mask[$i % 4];
        }
        return $decoded;
    }
    
    /**
     * Codificar WebSocket
     */
    private function encodeWebSocket($data) {
        $frame = [0x81];
        $length = strlen($data);
        
        if ($length <= 125) {
            $frame[] = $length;
        } elseif ($length <= 65535) {
            $frame[] = 126;
            $frame[] = ($length >> 8) & 0xFF;
            $frame[] = $length & 0xFF;
        } else {
            $frame[] = 127;
            for ($i = 0; $i < 8; $i++) {
                $frame[] = ($length >> (8 * (7 - $i))) & 0xFF;
            }
        }
        
        $frame = array_merge($frame, unpack('C*', $data));
        return call_user_func_array('pack', array_merge(['C*'], $frame));
    }
    
    /**
     * Enviar mensagem
     */
    private function sendMessage($client, $data) {
        $message = json_encode($data);
        $encoded = $this->encodeWebSocket($message);
        @fwrite($client, $encoded, strlen($encoded));
    }
    
    /**
     * Broadcast
     */
    private function broadcast($data) {
        $message = json_encode($data);
        $encoded = $this->encodeWebSocket($message);
        
        foreach ($this->clients as $client) {
            if ($client !== $this->server) {
                @fwrite($client, $encoded, strlen($encoded));
            }
        }
    }
    
    /**
     * Remover cliente
     */
    private function removeClient($client) {
        $index = array_search($client, $this->clients);
        if ($index !== false) {
            unset($this->clients[$index]);
            $this->clients = array_values($this->clients);
            $this->logEvent("🔴 Cliente desconectado: " . count($this->clients));
        }
        @fclose($client);
    }
    
    /**
     * Handler de mensagens
     */
    private function handleMessage($client, $data) {
        $decoded = json_decode($data, true);
        if (!$decoded) return;
        
        switch ($decoded['type'] ?? '') {
            case 'ping':
                $this->sendMessage($client, ['type' => 'pong', 'timestamp' => date('Y-m-d H:i:s')]);
                break;
                
            case 'get_vehicles':
                $this->sendMessage($client, [
                    'type' => 'vehicles.list',
                    'vehicles' => array_values($this->vehicles)
                ]);
                break;
                
            case 'get_deliveries':
                $this->sendMessage($client, [
                    'type' => 'deliveries.list',
                    'deliveries' => $this->deliveries
                ]);
                break;
                
            case 'send.message':
                $this->broadcast([
                    'type' => 'message.sent',
                    'driver_id' => $decoded['driver_id'] ?? null,
                    'message' => $decoded['message'] ?? '',
                    'sender' => $decoded['sender'] ?? 'Sistema',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                $this->sendMessage($client, [
                    'type' => 'message.confirmed',
                    'message' => '✅ Mensagem enviada com sucesso'
                ]);
                $this->logEvent("📨 Mensagem enviada: " . ($decoded['message'] ?? ''));
                break;
        }
    }
    
    /**
     * Atualizar veículos
     */
    private function updateVehicles() {
        foreach ($this->vehicles as &$vehicle) {
            // Movimento suave
            $vehicle['latitude'] += (mt_rand(-20, 20) / 100000);
            $vehicle['longitude'] += (mt_rand(-20, 20) / 100000);
            $vehicle['speed'] = mt_rand(0, 80);
            $vehicle['last_update'] = date('Y-m-d H:i:s');
            
            // Mudar status ocasionalmente
            if (mt_rand(1, 100) < 5) {
                $statuses = ['active', 'in_route', 'idle'];
                $newStatus = $statuses[array_rand($statuses)];
                if ($vehicle['status'] !== $newStatus) {
                    $vehicle['status'] = $newStatus;
                    $this->broadcast([
                        'type' => 'vehicle.status_changed',
                        'vehicle_id' => $vehicle['id'],
                        'plate' => $vehicle['plate'],
                        'status' => $newStatus,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
        
        // Broadcast atualização
        $this->broadcast([
            'type' => 'vehicles.updated',
            'vehicles' => array_values($this->vehicles),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Gerar evento aleatório realista
     */
    private function generateRandomEvent() {
        $events = [
            [
                'type' => 'delivery_completed',
                'message' => '📦 Entrega #' . $this->deliveryId . ' concluída por ' . $this->vehicles[array_rand($this->vehicles)]['driver_name'],
                'callback' => function() {
                    // Atualizar entrega
                    if (count($this->deliveries) > 0) {
                        $idx = array_rand($this->deliveries);
                        $this->deliveries[$idx]['status'] = 'delivered';
                        $this->deliveries[$idx]['completed_at'] = date('Y-m-d H:i:s');
                    }
                }
            ],
            [
                'type' => 'delivery_assigned',
                'message' => '📦 Entrega #' . $this->deliveryId . ' atribuída a ' . $this->vehicles[array_rand($this->vehicles)]['driver_name'],
                'callback' => function() {
                    // Nova entrega
                    $customers = ['João Silva', 'Maria Santos', 'Pedro Costa', 'Ana Oliveira', 'Carlos Rodrigues'];
                    $addresses = ['Rua A, 123', 'Av. B, 456', 'Travessa C, 789', 'Rua D, 321', 'Av. E, 654'];
                    $this->deliveries[] = [
                        'id' => $this->deliveryId++,
                        'customer_name' => $customers[array_rand($customers)],
                        'customer_address' => $addresses[array_rand($addresses)],
                        'status' => 'assigned',
                        'vehicle_plate' => $this->vehicles[array_rand($this->vehicles)]['plate'],
                        'driver_name' => $this->vehicles[array_rand($this->vehicles)]['driver_name'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    if (count($this->deliveries) > 50) {
                        array_shift($this->deliveries);
                    }
                }
            ],
            [
                'type' => 'route_started',
                'message' => '🗺️ Rota #' . mt_rand(100, 999) . ' iniciada por ' . $this->vehicles[array_rand($this->vehicles)]['driver_name']
            ],
            [
                'type' => 'alert',
                'message' => '🚨 Alerta: Veículo ' . $this->vehicles[array_rand($this->vehicles)]['plate'] . ' excedeu velocidade permitida',
                'callback' => function() {
                    // Adicionar alerta
                    $this->alerts[] = [
                        'id' => count($this->alerts) + 1,
                        'title' => 'Excesso de Velocidade',
                        'message' => 'Veículo ' . $this->vehicles[array_rand($this->vehicles)]['plate'] . ' excedeu o limite de velocidade',
                        'type' => 'speeding',
                        'severity' => 'critical',
                        'is_read' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    if (count($this->alerts) > 20) {
                        array_shift($this->alerts);
                    }
                }
            ],
            [
                'type' => 'vehicle_online',
                'message' => '✅ Veículo ' . $this->vehicles[array_rand($this->vehicles)]['plate'] . ' ficou online'
            ],
            [
                'type' => 'vehicle_offline',
                'message' => '⚠️ Veículo ' . $this->vehicles[array_rand($this->vehicles)]['plate'] . ' ficou offline'
            ],
            [
                'type' => 'delivery_delayed',
                'message' => '⏰ Entrega #' . mt_rand(1000, 9999) . ' está atrasada'
            ]
        ];
        
        $event = $events[array_rand($events)];
        
        // Executar callback se existir
        if (isset($event['callback'])) {
            $event['callback']->call($this);
        }
        
        $this->addEvent($event['type'], $event['message']);
        
        // Broadcast do evento
        $this->broadcast([
            'type' => 'event',
            'data' => [
                'id' => $this->eventId,
                'type' => $event['type'],
                'message' => $event['message'],
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
        
        // Broadcast de dados atualizados
        if ($event['type'] === 'delivery_completed' || $event['type'] === 'delivery_assigned') {
            $this->broadcast([
                'type' => 'deliveries.updated',
                'deliveries' => $this->deliveries
            ]);
        }
        
        if ($event['type'] === 'alert') {
            $this->broadcast([
                'type' => 'alerts.updated',
                'alerts' => $this->alerts
            ]);
        }
        
        $this->logEvent($event['message']);
    }
    
    /**
     * Verificar entrada do usuário
     */
    private function checkInput() {
        static $lastCheck = 0;
        if (time() - $lastCheck < 1) return;
        $lastCheck = time();
        
        $read = [STDIN];
        $write = null;
        $except = null;
        
        if (stream_select($read, $write, $except, 0, 100)) {
            $input = trim(fgets(STDIN));
            if (strtolower($input) === 'q' || strtolower($input) === 'quit') {
                echo "\n";
                $this->logEvent("🛑 Servidor desligado pelo usuário");
                $this->running = false;
                $this->shutdown();
                exit(0);
            }
        }
    }
    
    /**
     * Log de eventos
     */
    private function logEvent($message) {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    }
    
    /**
     * Shutdown
     */
    public function shutdown() {
        $this->running = false;
        $this->logEvent("🛑 Desligando servidor...");
        
        foreach ($this->clients as $client) {
            if ($client !== $this->server) {
                @fclose($client);
            }
        }
        
        if ($this->server) {
            @fclose($this->server);
        }
        
        $uptime = round(microtime(true) - $this->startTime);
        $this->logEvent("⏱️  Tempo de atividade: " . gmdate("H:i:s", $uptime));
        $this->logEvent("✅ Servidor desligado com sucesso!");
        echo "\n";
    }
}

// ============================================
// INICIAR SERVIDOR
// ============================================

if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function() use (&$server) {
        echo "\n";
        if (isset($server)) {
            $server->shutdown();
        }
        exit(0);
    });
    pcntl_signal(SIGTERM, function() use (&$server) {
        echo "\n";
        if (isset($server)) {
            $server->shutdown();
        }
        exit(0);
    });
}

try {
    $server = new WebSocketServer();
    
    while ($server && $server->running) {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
        usleep(100000);
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}