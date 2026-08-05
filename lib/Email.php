<?php
/**
 * CARDOXIS - Email Class
 * Version: 2.0.0 (Production Ready)
 */

class Email {
    private $config;
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../logs/email.log';
        
        // Carregar configuração
        $configPath = __DIR__ . '/../config/email.php';
        if (file_exists($configPath)) {
            $this->config = include($configPath);
        } else {
            throw new Exception("Arquivo de configuração não encontrado");
        }
        
        // Criar diretório de logs
        if (!is_dir(__DIR__ . '/../logs')) {
            mkdir(__DIR__ . '/../logs', 0777, true);
        }
    }
    
    /**
     * Enviar email
     */
    public function send($to, $subject, $message, $from = null) {
        $mode = $this->config['mode'] ?? 'mailtrap';
        $smtpConfig = $this->config[$mode] ?? $this->config['mailtrap'];
        
        $this->log("Enviando email para: $to");
        $this->log("Modo: $mode");
        
        if ($mode === 'mailtrap') {
            return $this->sendMailtrap($to, $subject, $message, $smtpConfig);
        } else {
            return $this->sendSMTP($to, $subject, $message, $smtpConfig);
        }
    }
    
    /**
     * Enviar via Mailtrap (Desenvolvimento)
     */
    private function sendMailtrap($to, $subject, $message, $config) {
        return $this->sendSMTP(
            $to, 
            $subject, 
            $message, 
            [
                'host' => $config['host'],
                'port' => $config['port'],
                'username' => $config['username'],
                'password' => $config['password'],
                'from_email' => $config['from_email'],
                'from_name' => $config['from_name']
            ]
        );
    }
    
    /**
     * Enviar via SMTP
     */
    private function sendSMTP($to, $subject, $message, $config) {
        $host = $config['host'];
        $port = $config['port'];
        $username = $config['username'];
        $password = $config['password'];
        $fromEmail = $config['from_email'];
        $fromName = $config['from_name'];
        
        $socket = @fsockopen($host, $port, $errno, $errstr, 30);
        
        if (!$socket) {
            $this->log("Erro ao conectar: $errstr ($errno)");
            return false;
        }
        
        // Ler resposta inicial
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            $this->log("Resposta inesperada: $response");
            return false;
        }
        
        // EHLO
        fputs($socket, "EHLO " . gethostname() . "\r\n");
        $this->readResponse($socket);
        
        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            $this->log("AUTH LOGIN falhou");
            return false;
        }
        
        // Username
        fputs($socket, base64_encode($username) . "\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            $this->log("Username inválido");
            return false;
        }
        
        // Password
        fputs($socket, base64_encode($password) . "\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) != '235') {
            fclose($socket);
            $this->log("Autenticação falhou");
            return false;
        }
        
        // MAIL FROM
        fputs($socket, "MAIL FROM:<$fromEmail>\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            $this->log("MAIL FROM falhou");
            return false;
        }
        
        // RCPT TO
        fputs($socket, "RCPT TO:<$to>\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) != '250' && substr($response, 0, 3) != '251') {
            fclose($socket);
            $this->log("RCPT TO falhou");
            return false;
        }
        
        // DATA
        fputs($socket, "DATA\r\n");
        $response = $this->readResponse($socket);
        
        if (substr($response, 0, 3) != '354') {
            fclose($socket);
            $this->log("DATA falhou");
            return false;
        }
        
        // Construir email completo
        $fullMessage = "Subject: $subject\r\n";
        $fullMessage .= "From: $fromName <$fromEmail>\r\n";
        $fullMessage .= "To: $to\r\n";
        $fullMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
        $fullMessage .= "\r\n";
        $fullMessage .= $message;
        $fullMessage .= "\r\n.\r\n";
        
        fputs($socket, $fullMessage);
        $response = $this->readResponse($socket);
        
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        if (substr($response, 0, 3) != '250') {
            $this->log("Erro ao enviar mensagem: $response");
            return false;
        }
        
        $this->log("Email enviado com sucesso para: $to");
        return true;
    }
    
    /**
     * Ler resposta SMTP
     */
    private function readResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 1024)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }
    
    /**
     * Registrar log
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }
}