<?php
class UserController {
    private $pdo;
    private $auth;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->auth = new AuthMiddleware($pdo);
    }

    public function register($data) {
        $required = ['login', 'username', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                return json_encode(['error' => "Missing required field: $field"]);
            }
        }

        if (strlen($data['login']) < 2) {
            http_response_code(400);
            return json_encode(['error' => 'Login must be at least 2 characters']);
        }

        if (strtolower($data['login']) === 'admin1') {
            http_response_code(400);
            return json_encode(['error' => 'This login is reserved']);
        }

        if (strlen($data['username']) < 2) {
            http_response_code(400);
            return json_encode(['error' => 'Username must be at least 2 characters']);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return json_encode(['error' => 'Invalid email format']);
        }

        if (strlen($data['password']) < 2) {
            http_response_code(400);
            return json_encode(['error' => 'Password must be at least 2 characters']);
        }

        // Hash password
        $salt = '56s89_))(*$-890fghu2(!*&#@$09AQU';
        $passwordHash = md5($salt . $data['password']);

        try {
            $sql = "INSERT INTO users (login, username, email, password_hash)
                    VALUES (:login, :username, :email, :password_hash)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':login' => $data['login'],
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password_hash' => $passwordHash,
            ]);

            http_response_code(201);
            return json_encode(['message' => 'User registered']);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                http_response_code(400);
                return json_encode(['error' => 'Login or email already exists']);
            }
            http_response_code(500);
            return json_encode(['error' => 'Registration failed']);
        }
    }

    public function login($data) {
        if (empty($data['login']) || empty($data['password'])) {
            http_response_code(400);
            return json_encode(['error' => 'Login and password are required']);
        }

        $salt = '56s89_))(*$-890fghu2(!*&#@$09AQU';
        $passwordHash = md5($salt . $data['password']);

        $sql = "SELECT id, login, username, email FROM users
                WHERE login = :login AND password_hash = :password_hash";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':login' => $data['login'],
            ':password_hash' => $passwordHash,
        ]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(401);
            return json_encode(['error' => 'Invalid credentials']);
        }

        $token = $this->auth->generateJWT($user['id']);

        return json_encode([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'login' => $user['login'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ]);
    }

    public function getProfile($userId) {
        $sql = "SELECT id, login, username, email, created_at FROM users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            return json_encode(['error' => 'User not found']);
        }

        return json_encode($user);
    }

    public function getHistory($userId) {
        // Get purchases
        $sql = "SELECT id, film_id, film_title, price, created_at
                FROM user_purchases
                WHERE user_id = :user_id
                ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $purchases = $stmt->fetchAll();

        // Get views
        $sql = "SELECT id, film_id, film_title, created_at
                FROM user_views
                WHERE user_id = :user_id
                ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $views = $stmt->fetchAll();

        return json_encode([
            'purchases' => $purchases,
            'views' => $views
        ]);
    }

    public function getRatings($userId) {
        $sql = "SELECT r.id, r.film_id, r.score, r.created_at, f.title as film_title
                FROM ratings r
                LEFT JOIN films f ON r.film_id = f.id
                WHERE r.user_id = :user_id
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $ratings = $stmt->fetchAll();

        return json_encode([
            'ratings' => $ratings,
            'total' => count($ratings)
        ]);
    }

    public function getReviews($userId) {
        $sql = "SELECT r.id, r.film_id, r.text, r.created_at, f.title as film_title
                FROM reviews r
                LEFT JOIN films f ON r.film_id = f.id
                WHERE r.user_id = :user_id
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $reviews = $stmt->fetchAll();

        return json_encode([
            'reviews' => $reviews,
            'total' => count($reviews)
        ]);
    }

    public function getFullProfile($userId) {
        // Get profile
        $sql = "SELECT id, login, username, email, created_at FROM users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            return json_encode(['error' => 'User not found']);
        }

        // Get purchases
        $sql = "SELECT id, film_id, film_title, price, created_at
                FROM user_purchases
                WHERE user_id = :user_id
                ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $purchases = $stmt->fetchAll();

        // Get views
        $sql = "SELECT id, film_id, film_title, created_at
                FROM user_views
                WHERE user_id = :user_id
                ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $views = $stmt->fetchAll();

        // Get ratings
        $sql = "SELECT r.id, r.film_id, r.score, r.created_at, f.title as film_title
                FROM ratings r
                LEFT JOIN films f ON r.film_id = f.id
                WHERE r.user_id = :user_id
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $ratings = $stmt->fetchAll();

        // Get reviews
        $sql = "SELECT r.id, r.film_id, r.text, r.created_at, f.title as film_title
                FROM reviews r
                LEFT JOIN films f ON r.film_id = f.id
                WHERE r.user_id = :user_id
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $reviews = $stmt->fetchAll();

        return json_encode([
            'user' => $user,
            'purchases' => $purchases,
            'views' => $views,
            'ratings' => $ratings,
            'reviews' => $reviews
        ]);
    }

    public function addPurchase($userId, $data) {
        if (empty($data['film_id']) || empty($data['film_title']) || !isset($data['price'])) {
            http_response_code(400);
            return json_encode(['error' => 'Missing required fields: film_id, film_title, price']);
        }

        // Check if already purchased
        $sql = "SELECT id FROM user_purchases
                WHERE user_id = :user_id AND film_id = :film_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':film_id' => $data['film_id'],
        ]);

        if ($stmt->fetch()) {
            http_response_code(400);
            return json_encode(['error' => 'Film already purchased']);
        }

        try {
            $sql = "INSERT INTO user_purchases (user_id, film_id, film_title, price)
                    VALUES (:user_id, :film_id, :film_title, :price)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':film_id' => $data['film_id'],
                ':film_title' => $data['film_title'],
                ':price' => (int)$data['price'],
            ]);

            http_response_code(201);
            return json_encode(['message' => 'Purchase recorded']);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(['error' => 'Failed to record purchase']);
        }
    }

    public function addView($userId, $data) {
        if (empty($data['film_id']) || empty($data['film_title'])) {
            http_response_code(400);
            return json_encode(['error' => 'Missing required fields: film_id, film_title']);
        }

        try {
            $sql = "INSERT INTO user_views (user_id, film_id, film_title)
                    VALUES (:user_id, :film_id, :film_title)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':film_id' => $data['film_id'],
                ':film_title' => $data['film_title'],
            ]);

            http_response_code(201);
            return json_encode(['message' => 'View recorded']);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(['error' => 'Failed to record view']);
        }
    }
}
