<?php
/**
 * Authentication Middleware
 */

class AuthMiddleware {
    
    public function handle() {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        
        if (empty($token)) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Authentication token required'
            ]);
            exit();
        }
        
        // TODO: Validate JWT token
        // For now, accept any non-empty token
        
        return true;
    }
}

/**
 * Admin Authentication Middleware
 */
class AdminAuthMiddleware {
    
    public function handle() {
        $headers = getallheaders();
        $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
        
        if (empty($token)) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Authentication token required'
            ]);
            exit();
        }
        
        // TODO: Validate JWT token and check admin role
        // For demo, accept token and assume admin
        
        return true;
    }
}

/**
 * Rate Limit Middleware
 */
class RateLimitMiddleware {
    
    private $maxRequests = 100;
    private $timeWindow = 60; // seconds
    
    public function handle() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = 'rate_limit_' . $ip;
        
        // Simple file-based rate limiting
        $cacheFile = __DIR__ . '/../cache/' . $key . '.json';
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $requestCount = $data['count'];
            $firstRequest = $data['first_request'];
            
            if (time() - $firstRequest < $this->timeWindow) {
                if ($requestCount >= $this->maxRequests) {
                    http_response_code(429);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Too Many Requests',
                        'message' => 'Rate limit exceeded. Please try again later.'
                    ]);
                    exit();
                }
                
                $data['count']++;
                file_put_contents($cacheFile, json_encode($data));
            } else {
                // Reset rate limit
                file_put_contents($cacheFile, json_encode([
                    'count' => 1,
                    'first_request' => time()
                ]));
            }
        } else {
            file_put_contents($cacheFile, json_encode([
                'count' => 1,
                'first_request' => time()
            ]));
        }
        
        return true;
    }
}