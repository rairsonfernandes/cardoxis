<?php
/**
 * CARDOXIS - Chat Controller RF
 */

require_once __DIR__ . '/BaseController.php';

class ChatController extends BaseController {
    private $db;
    private $openaiApiKey;
    private $openaiModel;
    private $openaiMaxTokens;
    private $openaiTemperature;
    private $cacheDir;
    private $cacheTTL = 3600;
    private $rateLimitRequests = 30;
    private $rateLimitWindow = 60;
    private $minQuestionLength = 3;
    private $contextHistory = 5; // Número de mensagens para contexto
    
    // Sistema de Intenções
    private $intents = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->cacheDir = __DIR__ . '/../cache/chat/';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
        
        $this->loadOpenAIConfig();
        $this->loadIntents();
    }
    
    private function loadOpenAIConfig() {
        $this->openaiApiKey = getenv('OPENAI_API_KEY') ?: null;
        $this->openaiModel = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';
        $this->openaiMaxTokens = (int)(getenv('OPENAI_MAX_TOKENS') ?: 500);
        $this->openaiTemperature = (float)(getenv('OPENAI_TEMPERATURE') ?: 0.7);
        
        if (!$this->openaiApiKey || $this->openaiApiKey === 'YOUR_OPENAI_API_KEY') {
            try {
                $result = $this->db->fetchOne(
                    "SELECT setting_value FROM system_settings WHERE setting_key = 'openai_api_key'"
                );
                if ($result && !empty($result['setting_value'])) {
                    $this->openaiApiKey = $result['setting_value'];
                }
            } catch (Exception $e) {}
        }
        
        if (!$this->openaiApiKey || strpos($this->openaiApiKey, 'sk-') !== 0) {
            $this->openaiApiKey = null;
        }
    }
    
    private function loadIntents() {
        $this->intents = [
            'veiculo' => [
                'keywords' => ['veículo', 'veiculo', 'carro', 'viatura', 'automóvel', 'automovel', 'matrícula', 'matricula', 'marca', 'modelo'],
                'actions' => ['adicionar', 'novo', 'cadastrar', 'criar', 'registrar', 'incluir', 'editar', 'atualizar', 'modificar', 'alterar', 'mudar', 'eliminar', 'excluir', 'remover', 'apagar', 'deletar']
            ],
            'motorista' => [
                'keywords' => ['motorista', 'condutor', 'driver', 'funcionário', 'funcionario', 'colaborador', 'licença', 'carta'],
                'actions' => ['adicionar', 'novo', 'cadastrar', 'criar', 'registrar', 'incluir', 'editar', 'atualizar']
            ],
            'combustivel' => [
                'keywords' => ['combustível', 'combustivel', 'gasolina', 'gasóleo', 'gasoleo', 'diesel', 'abastecimento', 'litro', 'consumo', 'km', 'odômetro', 'odometro'],
                'actions' => ['registrar', 'adicionar', 'novo', 'cadastrar', 'criar', 'incluir', 'abastecer']
            ],
            'documento' => [
                'keywords' => ['documento', 'ficheiro', 'arquivo', 'upload', 'pdf', 'dua', 'livrete', 'seguro', 'fatura', 'recibo', 'anexo'],
                'actions' => ['upload', 'adicionar', 'enviar', 'carregar', 'anexar', 'incluir']
            ],
            'manutencao' => [
                'keywords' => ['manutenção', 'manutencao', 'revisão', 'revisao', 'reparação', 'reparacao', 'oficina', 'mecânico', 'mecanico', 'troca', 'óleo', 'oleo', 'filtro'],
                'actions' => ['agendar', 'programar', 'marcar', 'novo', 'criar', 'adicionar']
            ],
            'multa' => [
                'keywords' => ['multa', 'contra-ordenação', 'contraordenacao', 'infração', 'infraccao', 'contestação', 'contestacao', 'pagamento'],
                'actions' => ['registrar', 'adicionar', 'novo', 'cadastrar', 'criar', 'incluir', 'contestar']
            ],
            'seguro' => [
                'keywords' => ['seguro', 'apólice', 'apolice', 'sinistro', 'acidente', 'cobertura', 'renovação', 'renovacao'],
                'actions' => ['adicionar', 'novo', 'cadastrar', 'criar', 'registrar', 'incluir']
            ],
            'relatorio' => [
                'keywords' => ['relatório', 'relatorio', 'relatórios', 'relatorios', 'dados', 'estatística', 'estatistica', 'exportar', 'pdf', 'csv', 'excel'],
                'actions' => ['gerar', 'criar', 'fazer', 'produzir', 'obter']
            ],
            'alerta' => [
                'keywords' => ['alerta', 'notificação', 'notificacao', 'lembrete', 'aviso', 'notificar', 'lembrar'],
                'actions' => ['configurar', 'definir', 'ajustar', 'alterar', 'mudar', 'personalizar']
            ],
            'senha' => [
                'keywords' => ['senha', 'password', 'palavra-passe', 'palavrapasse', 'login', 'acesso', 'perfil', 'profile'],
                'actions' => ['alterar', 'mudar', 'trocar', 'reset', 'recuperar', 'esqueci', 'perdi']
            ],
            'empresa' => [
                'keywords' => ['empresa', 'companhia', 'organização', 'organizacao', 'administrador', 'admin', 'gestão', 'gestao', 'permissão', 'permissao', 'utilizador', 'usuario'],
                'actions' => ['adicionar', 'novo', 'cadastrar', 'criar', 'registrar', 'incluir', 'convidar']
            ]
        ];
    }
    
    private function detectIntent($question) {
        $questionLower = strtolower($question);
        $detectedIntents = [];
        
        foreach ($this->intents as $intent => $data) {
            $score = 0;
            
            // Verificar palavras-chave
            foreach ($data['keywords'] as $keyword) {
                if (strpos($questionLower, $keyword) !== false) {
                    $score += 2;
                }
            }
            
            // Verificar ações
            foreach ($data['actions'] as $action) {
                if (strpos($questionLower, $action) !== false) {
                    $score += 1;
                }
            }
            
            if ($score > 0) {
                $detectedIntents[$intent] = $score;
            }
        }
        
        // Ordenar por score
        arsort($detectedIntents);
        
        // Retornar a intenção com maior score
        return !empty($detectedIntents) ? key($detectedIntents) : null;
    }
    
    private function getContextHistory($userId, $limit = 5) {
        try {
            $history = $this->db->fetchAll("
                SELECT question, response 
                FROM chat_history 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit
            ", [
                ':user_id' => $userId,
                ':limit' => $limit
            ]);
            
            return array_reverse($history);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function canMakeRequest($userId) {
        $key = 'chat_requests_' . $userId;
        $file = $this->cacheDir . $key . '.json';
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['timestamp']) && isset($data['count'])) {
                $elapsed = time() - $data['timestamp'];
                if ($elapsed < $this->rateLimitWindow && $data['count'] >= $this->rateLimitRequests) {
                    return false;
                }
            }
        }
        return true;
    }
    
    private function logRequest($userId) {
        $key = 'chat_requests_' . $userId;
        $file = $this->cacheDir . $key . '.json';
        
        $data = ['timestamp' => time(), 'count' => 1];
        if (file_exists($file)) {
            $old = json_decode(file_get_contents($file), true);
            if ($old && isset($old['timestamp']) && isset($old['count'])) {
                $elapsed = time() - $old['timestamp'];
                if ($elapsed < $this->rateLimitWindow) {
                    $data['count'] = $old['count'] + 1;
                    $data['timestamp'] = $old['timestamp'];
                }
            }
        }
        
        file_put_contents($file, json_encode($data));
    }
    
    private function getCachedResponse($question, $userId) {
        $key = md5(strtolower(trim($question)) . '_' . $userId);
        $file = $this->cacheDir . 'response_' . $key . '.json';
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && (time() - $data['timestamp']) < $this->cacheTTL) {
                return $data['response'];
            }
        }
        return null;
    }
    
    private function saveCachedResponse($question, $response, $userId) {
        $key = md5(strtolower(trim($question)) . '_' . $userId);
        $file = $this->cacheDir . 'response_' . $key . '.json';
        file_put_contents($file, json_encode([
            'timestamp' => time(),
            'response' => $response
        ]));
    }
    
    public function status($input, $params) {
        try {
            $hasValidKey = $this->openaiApiKey && strpos($this->openaiApiKey, 'sk-') === 0;
            
            return $this->success([
                'has_key' => (bool)$hasValidKey,
                'is_working' => (bool)$hasValidKey,
                'model' => $this->openaiModel,
                'status' => $hasValidKey ? 'online' : 'offline',
                'message' => $hasValidKey ? 'OpenAI configurado' : 'Modo local ativo',
                'rate_limit' => [
                    'requests' => $this->rateLimitRequests,
                    'window' => $this->rateLimitWindow . 's'
                ]
            ]);
        } catch (Exception $e) {
            return $this->success([
                'has_key' => false,
                'is_working' => false,
                'status' => 'error',
                'message' => 'Erro ao verificar status'
            ]);
        }
    }
    
    public function ask($input, $params) {
        try {
            header('Content-Type: application/json');
            
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $question = trim($input['question'] ?? '');
            
            // Validar tamanho mínimo
            if (strlen($question) < $this->minQuestionLength) {
                return $this->success([
                    'response' => $this->getShortMessageResponse(),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'source' => 'validation'
                ]);
            }
            
            $userId = $authUser['user_id'];
            
            // Verificar cache (com userId)
            $cachedResponse = $this->getCachedResponse($question, $userId);
            if ($cachedResponse) {
                return $this->success([
                    'response' => $cachedResponse,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'source' => 'cache'
                ]);
            }
            
            // Rate Limiting
            if (!$this->canMakeRequest($userId)) {
                return $this->success([
                    'response' => $this->getSmartRateLimitResponse(),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'source' => 'rate_limited'
                ]);
            }
            
            $this->logRequest($userId);
            
            // Detectar intenção
            $intent = $this->detectIntent($question);
            
            // Buscar contexto da conversa
            $context = $this->getContextHistory($userId, $this->contextHistory);
            
            // Resposta baseada na intenção
            $response = $this->getIntentResponse($intent, $question, $authUser);
            
            // Tentar OpenAI (se disponível e pergunta relevante)
            if ($this->openaiApiKey && strlen($question) > 10) {
                try {
                    $openaiResponse = $this->askOpenAI($question, $authUser, $context);
                    if ($openaiResponse && strpos($openaiResponse, '⚠️') === false) {
                        $response = $openaiResponse;
                        $source = 'openai';
                        $this->saveCachedResponse($question, $response, $userId);
                    }
                } catch (Exception $e) {
                    error_log("[CHAT] OpenAI error: " . $e->getMessage());
                }
            }
            
            // Salvar histórico
            $this->saveChatHistory($userId, $question, $response);
            
            return $this->success([
                'response' => $response,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => $source ?? 'smart',
                'intent' => $intent
            ]);
            
        } catch (Exception $e) {
            error_log("[CHAT] ask error: " . $e->getMessage());
            return $this->success([
                'response' => 'Desculpe, ocorreu um erro. 😅 Por favor, tente novamente.',
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'fallback'
            ]);
        }
    }
    
    private function getIntentResponse($intent, $question, $user) {
        $questionLower = strtolower($question);
        $name = $user ? $user['user_name'] ?? 'Utilizador' : 'Utilizador';
        
        switch ($intent) {
            case 'veiculo':
                return $this->getVehicleResponse($questionLower, $name);
            case 'motorista':
                return $this->getDriverResponse($questionLower, $name);
            case 'combustivel':
                return $this->getFuelResponse($questionLower, $name);
            case 'documento':
                return $this->getDocumentResponse($questionLower, $name);
            case 'manutencao':
                return $this->getMaintenanceResponse($questionLower, $name);
            case 'multa':
                return $this->getFineResponse($questionLower, $name);
            case 'seguro':
                return $this->getInsuranceResponse($questionLower, $name);
            case 'relatorio':
                return $this->getReportResponse($questionLower, $name);
            case 'alerta':
                return $this->getAlertResponse($questionLower, $name);
            case 'senha':
                return $this->getPasswordResponse($questionLower, $name);
            case 'empresa':
                return $this->getCompanyResponse($questionLower, $name);
            default:
                return $this->getGenericResponse($questionLower, $name);
        }
    }
    
    private function getVehicleResponse($question, $name) {
        // Buscar dados reais do sistema
        $totalVehicles = $this->db->fetchOne("SELECT COUNT(*) as total FROM vehicles");
        $activeVehicles = $this->db->fetchOne("SELECT COUNT(*) as total FROM vehicles WHERE status = 'active'");
        
        $context = "";
        if ($totalVehicles && $activeVehicles) {
            $context = "\n\n📊 **Dados atuais:**\n" .
                      "• Total de veículos: **" . $totalVehicles['total'] . "**\n" .
                      "• Veículos ativos: **" . $activeVehicles['total'] . "**\n";
        }
        
        if (preg_match('/(adicionar|novo|cadastrar|criar|registrar|incluir)/', $question)) {
            return "🚗 **Como Adicionar um Veículo**\n\n" .
                   "1. Aceda ao menu **Veículos** no painel lateral\n" .
                   "2. Clique em **Novo Veículo**\n" .
                   "3. Preencha os dados:\n" .
                   "   • Matrícula (ex: AA-00-AA)\n" .
                   "   • Marca e Modelo\n" .
                   "   • Ano de Fabrico\n" .
                   "   • Quilometragem Atual\n" .
                   "   • Cor\n" .
                   "   • Número do Quadro\n" .
                   "4. Clique em **Guardar**\n\n" .
                   "💡 Pode adicionar fotos e documentos do veículo." .
                   $context;
        }
        
        if (preg_match('/(editar|atualizar|modificar|alterar|mudar)/', $question)) {
            return "✏️ **Como Editar um Veículo**\n\n" .
                   "1. Aceda ao menu **Veículos**\n" .
                   "2. Localize o veículo na lista\n" .
                   "3. Clique no ícone de **Editar** (lápis)\n" .
                   "4. Altere os dados necessários\n" .
                   "5. Clique em **Guardar**\n\n" .
                   "💡 Mantenha os dados sempre atualizados." .
                   $context;
        }
        
        if (preg_match('/(eliminar|excluir|remover|apagar|deletar)/', $question)) {
            return "🗑️ **Como Eliminar um Veículo**\n\n" .
                   "1. Aceda ao menu **Veículos**\n" .
                   "2. Localize o veículo na lista\n" .
                   "3. Clique no ícone de **Eliminar** (lixo)\n" .
                   "4. Confirme a eliminação\n\n" .
                   "⚠️ **Atenção:** Esta ação é irreversível!";
        }
        
        return "🚗 **Gestão de Veículos**\n\n" .
               "Funcionalidades disponíveis:\n" .
               "• ➕ Adicionar novo veículo\n" .
               "• ✏️ Editar dados do veículo\n" .
               "• 🗑️ Eliminar veículo\n" .
               "• 📄 Documentos do veículo\n" .
               "• 🔧 Manutenções\n" .
               "• 🛡️ Seguros\n" .
               "• 📊 Histórico de quilometragem\n\n" .
               "💡 O que gostaria de fazer?" .
               $context;
    }
    
    private function getDriverResponse($question, $name) {
        $totalDrivers = $this->db->fetchOne("SELECT COUNT(*) as total FROM drivers");
        
        $context = "";
        if ($totalDrivers) {
            $context = "\n\n📊 **Dados atuais:**\n" .
                      "• Total de motoristas: **" . $totalDrivers['total'] . "**\n";
        }
        
        if (preg_match('/(adicionar|novo|cadastrar|criar|registrar|incluir)/', $question)) {
            return "👤 **Como Adicionar um Motorista**\n\n" .
                   "1. Aceda ao menu **Motoristas**\n" .
                   "2. Clique em **Novo Motorista**\n" .
                   "3. Preencha os dados:\n" .
                   "   • Nome Completo\n" .
                   "   • Número de Documento (BI/CC)\n" .
                   "   • Número da Carta de Condução\n" .
                   "   • Data de Validade da Carta\n" .
                   "   • Contacto (telemóvel/email)\n" .
                   "4. Clique em **Guardar**\n\n" .
                   "🔔 Receberá alertas para renovação da carta." .
                   $context;
        }
        
        return "👤 **Gestão de Motoristas**\n\n" .
               "Funcionalidades:\n" .
               "• ➕ Adicionar novo motorista\n" .
               "• ✏️ Editar dados do motorista\n" .
               "• 🗑️ Eliminar motorista\n" .
               "• 📄 Documentos do motorista\n" .
               "• 🔔 Alertas de renovação de carta\n\n" .
               "💡 O que gostaria de fazer?" .
               $context;
    }
    
    private function getFuelResponse($question, $name) {
        $totalFuel = $this->db->fetchOne("SELECT SUM(liters) as total, SUM(cost) as total_cost FROM fuel_logs");
        
        $context = "";
        if ($totalFuel && $totalFuel['total']) {
            $context = "\n\n📊 **Dados atuais:**\n" .
                      "• Total abastecido: **" . number_format($totalFuel['total'], 2) . " L**\n" .
                      "• Custo total: **€" . number_format($totalFuel['total_cost'] ?? 0, 2) . "**\n";
        }
        
        if (preg_match('/(registrar|adicionar|novo|cadastrar|criar|incluir|abastecer)/', $question)) {
            return "⛽ **Como Registrar um Abastecimento**\n\n" .
                   "1. Aceda ao menu **Combustível**\n" .
                   "2. Clique em **Novo Abastecimento**\n" .
                   "3. Preencha os dados:\n" .
                   "   • Veículo (selecione)\n" .
                   "   • Data do abastecimento\n" .
                   "   • Quantidade (litros)\n" .
                   "   • Preço por litro (€)\n" .
                   "   • Odômetro atual (km)\n" .
                   "   • Tipo de combustível\n" .
                   "4. Clique em **Guardar**\n\n" .
                   "📊 Gere relatórios de consumo." .
                   $context;
        }
        
        return "⛽ **Gestão de Combustível**\n\n" .
               "Funcionalidades:\n" .
               "• ➕ Registrar abastecimento\n" .
               "• 📊 Relatórios de consumo\n" .
               "• 📈 Análise de custos\n" .
               "• 🔔 Alertas de consumo elevado\n\n" .
               "💡 O que gostaria de fazer?" .
               $context;
    }
    
    private function getDocumentResponse($question, $name) {
        if (preg_match('/(upload|adicionar|enviar|carregar|anexar|incluir)/', $question)) {
            return "📄 **Como Fazer Upload de Documentos**\n\n" .
                   "1. Aceda ao menu **Documentos**\n" .
                   "2. Clique em **Novo Documento**\n" .
                   "3. Preencha os dados:\n" .
                   "   • Título do documento\n" .
                   "   • Categoria (veículo/motorista/empresa)\n" .
                   "   • Data de validade (se aplicável)\n" .
                   "   • Ficheiro (PDF, JPG, PNG)\n" .
                   "4. Clique em **Guardar**\n\n" .
                   "🔔 Receba alertas de vencimento.";
        }
        
        return "📄 **Gestão de Documentos**\n\n" .
               "Funcionalidades:\n" .
               "• 📤 Upload de documentos\n" .
               "• 🏷️ Categorização automática\n" .
               "• 🔔 Alertas de vencimento\n" .
               "• 🔍 Busca avançada\n\n" .
               "💡 O que gostaria de fazer?";
    }
    
    private function getMaintenanceResponse($question, $name) {
        $pendingMaintenance = $this->db->fetchOne("SELECT COUNT(*) as total FROM maintenance WHERE status = 'pending'");
        
        $context = "";
        if ($pendingMaintenance && $pendingMaintenance['total'] > 0) {
            $context = "\n\n📊 **Dados atuais:**\n" .
                      "• Manutenções pendentes: **" . $pendingMaintenance['total'] . "**\n" .
                      "🔔 **Atenção:** Consulte o menu Manutenções para detalhes.\n";
        }
        
        if (preg_match('/(agendar|programar|marcar|novo|criar|adicionar)/', $question)) {
            return "🔧 **Como Agendar uma Manutenção**\n\n" .
                   "1. Aceda ao menu **Manutenções**\n" .
                   "2. Clique em **Nova Manutenção**\n" .
                   "3. Preencha os dados:\n" .
                   "   • Veículo\n" .
                   "   • Tipo (Preventiva/Corretiva)\n" .
                   "   • Data prevista\n" .
                   "   • Descrição do serviço\n" .
                   "   • Quilometragem atual\n" .
                   "4. Clique em **Guardar**\n\n" .
                   "🔔 Receba alertas automáticos." .
                   $context;
        }
        
        return "🔧 **Gestão de Manutenções**\n\n" .
               "Tipos:\n" .
               "• 🔄 Preventiva - Programada (revisões)\n" .
               "• ⚡ Corretiva - Reparação de avarias\n\n" .
               "Funcionalidades:\n" .
               "• ➕ Agendar manutenção\n" .
               "• 📋 Histórico de manutenções\n" .
               "• 🔔 Alertas automáticos\n\n" .
               "💡 O que gostaria de fazer?" .
               $context;
    }
    
    private function getFineResponse($question, $name) {
        $totalFines = $this->db->fetchOne("SELECT COUNT(*) as total, SUM(amount) as total_amount FROM fines");
        
        $context = "";
        if ($totalFines && $totalFines['total'] > 0) {
            $context = "\n\n📊 **Dados atuais:**\n" .
                      "• Total de multas: **" . $totalFines['total'] . "**\n" .
                      "• Valor total: **€" . number_format($totalFines['total_amount'] ?? 0, 2) . "**\n";
        }
        
        if (preg_match('/(registrar|adicionar|novo|cadastrar|criar|incluir)/', $question)) {
            return "💰 **Como Registrar uma Multa**\n\n" .
                   "1. Aceda ao menu **Multas**\n" .
                   "2. Clique em **Nova Multa**\n" .
                   "3. Preencha os dados:\n" .
                   "   • Veículo\n" .
                   "   • Número da multa\n" .
                   "   • Data da infração\n" .
                   "   • Valor\n" .
                   "   • Data de pagamento\n" .
                   "4. Clique em **Guardar**\n\n" .
                   "🔔 Alertas de prazos de pagamento." .
                   $context;
        }
        
        return "💰 **Gestão de Multas**\n\n" .
               "Funcionalidades:\n" .
               "• ➕ Registrar multa\n" .
               "• 💳 Pagamento integrado\n" .
               "• ⚖️ Contestação de multas\n" .
               "• 🔔 Alertas de prazos\n\n" .
               "💡 O que gostaria de fazer?" .
               $context;
    }
    
    private function getInsuranceResponse($question, $name) {
        if (preg_match('/(adicionar|novo|cadastrar|criar|registrar|incluir)/', $question)) {
            return "🛡️ **Como Adicionar um Seguro**\n\n" .
                   "1. Aceda ao menu **Seguros**\n" .
                   "2. Clique em **Novo Seguro**\n" .
                   "3. Preencha os dados:\n" .
                   "   • Veículo\n" .
                   "   • Tipo de seguro\n" .
                   "   • Número da apólice\n" .
                   "   • Data de início\n" .
                   "   • Data de vencimento\n" .
                   "   • Valor\n" .
                   "4. Clique em **Guardar**\n\n" .
                   "🔔 Alertas de renovação.";
        }
        
        return "🛡️ **Gestão de Seguros**\n\n" .
               "Funcionalidades:\n" .
               "• ➕ Adicionar seguro\n" .
               "• 📋 Gestão de apólices\n" .
               "• 🔔 Alertas de renovação\n" .
               "• 💰 Registo de sinistros\n\n" .
               "💡 O que gostaria de fazer?";
    }
    
    private function getReportResponse($question, $name) {
        if (preg_match('/(gerar|criar|fazer|produzir|obter)/', $question)) {
            return "📊 **Como Gerar um Relatório**\n\n" .
                   "1. Aceda ao menu **Relatórios**\n" .
                   "2. Selecione o tipo de relatório:\n" .
                   "   • 🚗 Veículos\n" .
                   "   • 👤 Motoristas\n" .
                   "   • ⛽ Combustível\n" .
                   "   • 💰 Multas\n" .
                   "   • 🔧 Manutenções\n" .
                   "3. Configure os filtros\n" .
                   "4. Clique em **Gerar**\n" .
                   "5. Escolha o formato: PDF, CSV ou Excel";
        }
        
        return "📊 **Relatórios Disponíveis**\n\n" .
               "Tipos:\n" .
               "• 🚗 Veículos\n" .
               "• 👤 Motoristas\n" .
               "• ⛽ Combustível\n" .
               "• 💰 Multas\n" .
               "• 🔧 Manutenções\n" .
               "• 📄 Documentos\n\n" .
               "📤 Exporte em PDF, CSV ou Excel.";
    }
    
    private function getAlertResponse($question, $name) {
        if (preg_match('/(configurar|definir|ajustar|alterar|mudar|personalizar)/', $question)) {
            return "⚙️ **Como Configurar Alertas**\n\n" .
                   "1. Aceda ao menu **Configurações**\n" .
                   "2. Selecione **Alertas**\n" .
                   "3. Configure as preferências:\n" .
                   "   • 📧 Alertas por email\n" .
                   "   • 🔔 Notificações no dashboard\n" .
                   "   • ⏰ Dias de antecedência\n" .
                   "   • 📋 Tipos de alertas\n" .
                   "4. Clique em **Guardar**\n\n" .
                   "💡 Recomendamos ativar todos os alertas.";
        }
        
        return "🔔 **Sistema de Alertas**\n\n" .
               "Alertas automáticos para:\n" .
               "• 📄 Vencimento de documentos\n" .
               "• 🔧 Manutenções programadas\n" .
               "• 🛡️ Renovação de seguros\n" .
               "• 💰 Prazos de multas\n" .
               "• 📅 Licenças de motoristas\n\n" .
               "📱 Receba notificações no dashboard e por email.";
    }
    
    private function getPasswordResponse($question, $name) {
        if (preg_match('/(alterar|mudar|trocar|reset|recuperar|esqueci|perdi)/', $question)) {
            return "🔐 **Como Alterar a Palavra-Passe**\n\n" .
                   "1. Clique no seu **nome/perfil** no canto superior direito\n" .
                   "2. Selecione **Perfil** ou **Configurações**\n" .
                   "3. Vá ao separador **Segurança**\n" .
                   "4. Insira a **senha atual**\n" .
                   "5. Insira a **nova senha** (mínimo 8 caracteres)\n" .
                   "6. **Confirme** a nova senha\n" .
                   "7. Clique em **Guardar**\n\n" .
                   "🔒 Use letras, números e símbolos para maior segurança.";
        }
        
        return "🔐 **Segurança da Conta**\n\n" .
               "Opções:\n" .
               "• 🔑 Alterar palavra-passe\n" .
               "• 📧 Alterar email\n" .
               "• 📱 Ativar verificação em 2 passos\n" .
               "• 📋 Histórico de acessos\n\n" .
               "💡 O que gostaria de fazer?";
    }
    
    private function getCompanyResponse($question, $name) {
        if (preg_match('/(adicionar|novo|cadastrar|criar|registrar|incluir|convidar)/', $question)) {
            return "👥 **Como Adicionar um Utilizador**\n\n" .
                   "1. Aceda ao menu **Utilizadores**\n" .
                   "2. Clique em **Novo Utilizador**\n" .
                   "3. Preencha os dados:\n" .
                   "   • Nome completo\n" .
                   "   • Email\n" .
                   "   • Função (Admin/Manager/User)\n" .
                   "   • Permissões\n" .
                   "4. Clique em **Guardar**\n\n" .
                   "📧 O utilizador receberá um email de convite.";
        }
        
        return "🏢 **Gestão de Empresas**\n\n" .
               "Funcionalidades para administradores:\n" .
               "• 👥 Gestão de utilizadores\n" .
               "• 🔐 Controlo de permissões\n" .
               "• 📊 Visão consolidada da frota\n" .
               "• 📈 Relatórios agregados\n\n" .
               "💡 O que gostaria de fazer?";
    }
    
    private function getGenericResponse($question, $name) {
        // Verificar saudações
        if (preg_match('/^(olá|oi|ola|bom dia|boa tarde|boa noite|hey|hi|hello|alô|alo|e ai|e aí|tudo bem|como vai|como está|como é que)/', $question)) {
            return "Olá, **{$name}**! 👋\n\n" .
                   "Como posso ajudá-lo hoje?\n\n" .
                   "📌 **Perguntas frequentes:**\n" .
                   "• 🚗 Como adicionar um veículo?\n" .
                   "• 👤 Como adicionar um motorista?\n" .
                   "• ⛽ Como registrar um abastecimento?\n" .
                   "• 📄 Como funciona a gestão de documentos?\n" .
                   "• 📊 Como gerar relatórios?\n\n" .
                   "💡 Digite sua pergunta completa e eu ajudo!";
        }
        
        return "Obrigado pela sua pergunta, **{$name}**! 😊\n\n" .
               "📌 **Posso ajudar com:**\n" .
               "• 🚗 **Veículos** - Cadastro, manutenção, documentos\n" .
               "• 👤 **Motoristas** - Gestão, licenças, documentos\n" .
               "• ⛽ **Combustível** - Abastecimentos, análise de custos\n" .
               "• 📄 **Documentos** - Upload, alertas de vencimento\n" .
               "• 🔧 **Manutenções** - Preventivas, corretivas\n" .
               "• 💰 **Multas** - Registro, pagamentos\n" .
               "• 🛡️ **Seguros** - Apólices, vencimentos\n" .
               "• 📊 **Relatórios** - Personalizados, exportação\n" .
               "• 🔔 **Alertas** - Notificações automáticas\n" .
               "• 🔐 **Senha** - Alteração e segurança\n\n" .
               "💡 **Dica:** Seja específico na pergunta!\n" .
               "Exemplo: *Como adicionar um novo veículo?*\n\n" .
               "Como posso ajudá-lo hoje?";
    }
    
    private function getShortMessageResponse() {
        return "👋 **Olá!** \n\n" .
               "Para eu poder ajudar, por favor escreva uma pergunta completa. 😊\n\n" .
               "📌 **Exemplos de perguntas:**\n" .
               "• 🚗 Como adicionar um veículo?\n" .
               "• 👤 Como adicionar um motorista?\n" .
               "• ⛽ Como registrar um abastecimento?\n" .
               "• 📄 Como funciona a gestão de documentos?\n" .
               "• 📊 Como gerar relatórios?\n" .
               "• 🔐 Como alterar a palavra-passe?\n\n" .
               "💡 O que gostaria de saber?";
    }
    
    private function getSmartRateLimitResponse() {
        return "⏳ **Muitas perguntas seguidas!** \n\n" .
               "Por favor, aguarde alguns segundos. 😊\n\n" .
               "📌 **Enquanto espera, que tal:**\n" .
               "• 🚗 Como adicionar um veículo?\n" .
               "• 👤 Como adicionar um motorista?\n" .
               "• ⛽ Como registrar um abastecimento?\n" .
               "• 📄 Como funciona a gestão de documentos?\n\n" .
               "💡 Escreva sua pergunta completa e eu respondo!";
    }
    
    private function askOpenAI($question, $user, $context = []) {
        $systemPrompt = "Você é o assistente do CARDOXIS, plataforma de gestão de frotas.\n" .
                        "Responda em Português de Portugal. Seja claro e objetivo.\n\n" .
                        "UTILIZADOR: " . ($user['user_name'] ?? 'Utilizador') . "\n" .
                        "FUNÇÃO: " . ($user['role'] ?? 'user');
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        
        // Adicionar contexto
        foreach ($context as $item) {
            $messages[] = ['role' => 'user', 'content' => $item['question']];
            $messages[] = ['role' => 'assistant', 'content' => $item['response']];
        }
        
        $messages[] = ['role' => 'user', 'content' => $question];
        
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        $data = [
            'model' => $this->openaiModel,
            'messages' => $messages,
            'temperature' => $this->openaiTemperature,
            'max_tokens' => $this->openaiMaxTokens
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openaiApiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            }
        }
        
        return null;
    }
    
    private function saveChatHistory($userId, $question, $response) {
        try {
            $this->db->insert('chat_history', [
                'user_id' => $userId,
                'question' => $question,
                'response' => $response,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {}
    }
    
    public function history($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $limit = isset($params['limit']) ? min((int)$params['limit'], 50) : 20;
            
            $history = $this->db->fetchAll("
                SELECT id, question, response, created_at
                FROM chat_history
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :limit
            ", [
                ':user_id' => $authUser['user_id'],
                ':limit' => $limit
            ]);
            
            return $this->success($history);
        } catch (Exception $e) {
            return $this->success([]);
        }
    }
    
    public function clearHistory($input, $params) {
        try {
            $authUser = $this->getAuthUser();
            if (!$authUser) {
                return $this->error('Não autenticado', null, 401);
            }
            
            $this->db->delete('chat_history', 'user_id = :user_id', [
                ':user_id' => $authUser['user_id']
            ]);
            
            return $this->success(null, 'Histórico limpo com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao limpar histórico', null, 500);
        }
    }
}