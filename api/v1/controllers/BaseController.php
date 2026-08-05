<?php
/**
 * CARDOXIS - Base Controller RF
 */

class BaseController {
    
    /**
     * Resposta de sucesso padronizada
     * 
     * @param mixed $data Dados a serem retornados
     * @param string $message Mensagem de sucesso
     * @param int $code Código HTTP
     * @param array $meta Metadados adicionais (paginacao, etc)
     * @return array Resposta formatada
     */
    protected function success($data = null, $message = 'Success', $code = 200, $meta = null) {
        http_response_code($code);
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($meta) {
            $response['meta'] = $meta;
        }
        
        return $response;
    }
    
    /**
     * Resposta de erro padronizada
     * 
     * @param string $message Mensagem de erro
     * @param mixed $error Dados do erro
     * @param int $code Código HTTP
     * @return array Resposta formatada
     */
    protected function error($message = 'Error', $error = null, $code = 400) {
        http_response_code($code);
        return [
            'success' => false,
            'message' => $message,
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Validar campos obrigatórios
     * 
     * @param array $data Dados a serem validados
     * @param array $fields Lista de campos obrigatórios
     * @return mixed True se válido, ou resposta de erro
     */
    protected function validateRequired($data, $fields) {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            return $this->error('Campos obrigatórios faltando', ['fields' => $missing], 400);
        }
        
        return true;
    }
    
    /**
     * Validar email
     * 
     * @param string $email Email a ser validado
     * @return bool
     */
    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Validar documento (CPF/CNPJ)
     * 
     * @param string $document Documento a ser validado
     * @return bool
     */
    protected function validateDocument($document) {
        $document = preg_replace('/[^0-9]/', '', $document);
        
        if (strlen($document) === 11) {
            return $this->validateCPF($document);
        } elseif (strlen($document) === 14) {
            return $this->validateCNPJ($document);
        }
        
        return false;
    }
    
    /**
     * Validar CPF
     * 
     * @param string $cpf CPF a ser validado
     * @return bool
     */
    protected function validateCPF($cpf) {
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        return true;
    }
    
    /**
     * Validar CNPJ
     * 
     * @param string $cnpj CNPJ a ser validado
     * @return bool
     */
    protected function validateCNPJ($cnpj) {
        if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
        
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) return false;
        
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
    
    /**
     * Obter parâmetros de paginação
     * 
     * @param array $params Parâmetros da requisição
     * @return array Parâmetros normalizados
     */
    protected function getPaginationParams($params) {
        return [
            'page' => max(1, (int)($params['page'] ?? 1)),
            'limit' => min(100, max(1, (int)($params['limit'] ?? 15))),
            'sort' => $params['sort'] ?? 'created_at',
            'order' => strtoupper($params['order'] ?? 'DESC')
        ];
    }
    
    /**
     * Obter usuário autenticado a partir do token JWT
     * 
     * @return array|null Dados do usuário ou null se não autenticado
     */
    protected function getAuthUser() {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        
        if (empty($token)) {
            return null;
        }
        
        // Decodificar o token JWT
        $decoded = json_decode(base64_decode($token), true);
        
        if (!$decoded || !isset($decoded['user_id'])) {
            return null;
        }
        
        return [
            'user_id' => $decoded['user_id'],
            'email' => $decoded['email'] ?? '',
            'role' => $decoded['role'] ?? 'user',
            'exp' => $decoded['exp'] ?? 0,
            'iat' => $decoded['iat'] ?? 0
        ];
    }
    
    /**
     * Verificar se o token expirou
     * 
     * @param array $authUser Dados do usuário autenticado
     * @return bool
     */
    protected function isTokenExpired($authUser) {
        if (!isset($authUser['exp']) || $authUser['exp'] === 0) {
            return false;
        }
        return time() > $authUser['exp'];
    }
    
    /**
     * Verificar se o usuário é admin
     * 
     * @param array $authUser Dados do usuário autenticado
     * @return bool
     */
    protected function isAdmin($authUser) {
        if (!$authUser) {
            return false;
        }
        return in_array($authUser['role'] ?? '', ['admin', 'super_admin']);
    }
}