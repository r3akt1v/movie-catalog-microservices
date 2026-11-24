<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/RatingsController.php';

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
$controller = new RatingsController($db->getConnection());

try {
    // POST rating
    if ($path === '/ratings' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo $controller->addRating($data);
    }
    // GET film ratings
    elseif (preg_match('#^/films/([^/]+)/ratings$#', $path, $matches) && $method === 'GET') {
        $filmId = $matches[1];
        echo $controller->getFilmRatings($filmId);
    }
    // POST review
    elseif ($path === '/reviews' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo $controller->addReview($data);
    }
    // GET film reviews
    elseif (preg_match('#^/films/([^/]+)/reviews$#', $path, $matches) && $method === 'GET') {
        $filmId = $matches[1];
        echo $controller->getFilmReviews($filmId);
    }
    // PUT review
    elseif (preg_match('#^/reviews/(\d+)$#', $path, $matches) && $method === 'PUT') {
        $reviewId = $matches[1];
        $data = json_decode(file_get_contents('php://input'), true);
        echo $controller->updateReview($reviewId, $data);
    }
    // DELETE review
    elseif (preg_match('#^/reviews/(\d+)$#', $path, $matches) && $method === 'DELETE') {
        $reviewId = $matches[1];
        echo $controller->deleteReview($reviewId);
    }
    else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
