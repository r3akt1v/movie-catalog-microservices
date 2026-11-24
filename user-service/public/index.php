<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserController.php';
require_once __DIR__ . '/../src/AuthMiddleware.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

$db = new Database();
$controller = new UserController($db->getConnection());
$auth = new AuthMiddleware($db->getConnection());

try {
    // Register
    if ($path === '/auth/register' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo $controller->register($data);
    }
    // Login
    elseif ($path === '/auth/login' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo $controller->login($data);
    }
    // Logout
    elseif ($path === '/auth/logout' && $method === 'POST') {
        echo json_encode(['message' => 'Logged out']);
    }
    // Get user profile (protected)
    elseif ($path === '/user/profile' && $method === 'GET') {
        $userId = $auth->authenticate();
        echo $controller->getProfile($userId);
    }
    // Get user history (protected)
    elseif ($path === '/user/history' && $method === 'GET') {
        $userId = $auth->authenticate();
        echo $controller->getHistory($userId);
    }
    // Add purchase (protected)
    elseif ($path === '/user/history/purchase' && $method === 'POST') {
        $userId = $auth->authenticate();
        $data = json_decode(file_get_contents('php://input'), true);
        echo $controller->addPurchase($userId, $data);
    }
    // Add view (protected)
    elseif ($path === '/user/history/view' && $method === 'POST') {
        $userId = $auth->authenticate();
        $data = json_decode(file_get_contents('php://input'), true);
        echo $controller->addView($userId, $data);
    }
    // Get user ratings (protected)
    elseif ($path === '/user/ratings' && $method === 'GET') {
        $userId = $auth->authenticate();
        echo $controller->getRatings($userId);
    }
    // Get user reviews (protected)
    elseif ($path === '/user/reviews' && $method === 'GET') {
        $userId = $auth->authenticate();
        echo $controller->getReviews($userId);
    }
    // Get full user profile (protected)
    elseif ($path === '/user/full-profile' && $method === 'GET') {
        $userId = $auth->authenticate();
        echo $controller->getFullProfile($userId);
    }
    else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
