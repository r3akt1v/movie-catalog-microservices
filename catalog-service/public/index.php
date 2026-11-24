<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/CatalogController.php';

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
$controller = new CatalogController($db->getConnection());

try {
    // Get all films
    if ($path === '/films' && $method === 'GET') {
        $genre = $_GET['genre'] ?? null;
        $search = $_GET['search'] ?? null;
        echo $controller->getAllFilms($genre, $search);
    }
    // Get single film
    elseif (preg_match('#^/films/([^/]+)$#', $path, $matches) && $method === 'GET') {
        $filmId = $matches[1];
        echo $controller->getFilm($filmId);
    }
    // Add film (admin)
    elseif ($path === '/films' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo $controller->addFilm($data);
    }
    // Update film (admin)
    elseif (preg_match('#^/films/([^/]+)$#', $path, $matches) && $method === 'PUT') {
        $filmId = $matches[1];
        $data = json_decode(file_get_contents('php://input'), true);
        echo $controller->updateFilm($filmId, $data);
    }
    // Delete film (admin)
    elseif (preg_match('#^/films/([^/]+)$#', $path, $matches) && $method === 'DELETE') {
        $filmId = $matches[1];
        echo $controller->deleteFilm($filmId);
    }
    else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
