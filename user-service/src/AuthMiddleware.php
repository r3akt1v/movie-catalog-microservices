<?php
class AuthMiddleware {
    private $pdo;
    private $secret;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->secret = getenv('JWT_SECRET') ?: 'default-secret-key';
    }

    public function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing or invalid Authorization header']);
            exit;
        }

        $token = $matches[1];
        $payload = $this->decodeJWT($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }

        return $payload['user_id'];
    }

    private function decodeJWT($token) {
        // Simple JWT implementation (for production use firebase/php-jwt)
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        list($header, $payload, $signature) = $parts;

        // Verify signature
        $expectedSignature = hash_hmac('sha256', "$header.$payload", $this->secret, true);
        $expectedSignature = rtrim(strtr(base64_encode($expectedSignature), '+/', '-_'), '=');

        if ($signature !== $expectedSignature) {
            return null;
        }

        // Decode payload
        $payload = json_decode(base64_decode($payload), true);

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    public function generateJWT($userId) {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode([
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + 3600 // 1 hour
        ]));

        $signature = hash_hmac('sha256', "$header.$payload", $this->secret, true);
        $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "$header.$payload.$signature";
    }
}
