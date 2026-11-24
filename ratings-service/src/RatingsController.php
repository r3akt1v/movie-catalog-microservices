<?php
class RatingsController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addRating($data) {
        $required = ['user_id', 'film_id', 'score'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                return json_encode(['error' => "Missing required field: $field"]);
            }
        }

        $score = (int)$data['score'];
        if ($score < 1 || $score > 10) {
            http_response_code(400);
            return json_encode(['error' => 'Score must be between 1 and 10']);
        }

        try {
            $sql = "INSERT INTO ratings (user_id, film_id, score)
                    VALUES (:user_id, :film_id, :score)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => (int)$data['user_id'],
                ':film_id' => $data['film_id'],
                ':score' => $score,
            ]);

            http_response_code(201);
            return json_encode(['message' => 'Rating saved']);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(['error' => 'Failed to save rating']);
        }
    }

    public function getFilmRatings($filmId) {
        $sql = "SELECT score, COUNT(*) as count
                FROM ratings
                WHERE film_id = :film_id
                GROUP BY score
                ORDER BY score";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':film_id' => $filmId]);
        $ratings = $stmt->fetchAll();

        // Calculate average
        $sql = "SELECT AVG(score) as avg_score, COUNT(*) as total_count
                FROM ratings
                WHERE film_id = :film_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':film_id' => $filmId]);
        $stats = $stmt->fetch();

        return json_encode([
            'film_id' => $filmId,
            'average_score' => $stats['avg_score'] ? round($stats['avg_score'], 1) : null,
            'total_ratings' => $stats['total_count'] ?: 0,
            'distribution' => $ratings
        ]);
    }

    public function addReview($data) {
        $required = ['user_id', 'film_id'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                return json_encode(['error' => "Missing required field: $field"]);
            }
        }

        $text = isset($data['text']) ? trim($data['text']) : null;
        if ($text !== null && strlen($text) > 10000) {
            http_response_code(400);
            return json_encode(['error' => 'Review text must be at most 10000 characters']);
        }

        try {
            $sql = "INSERT INTO reviews (user_id, film_id, text)
                    VALUES (:user_id, :film_id, :text)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => (int)$data['user_id'],
                ':film_id' => $data['film_id'],
                ':text' => $text,
            ]);

            http_response_code(201);
            return json_encode(['message' => 'Review saved']);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(['error' => 'Failed to save review']);
        }
    }

    public function getFilmReviews($filmId) {
        $sql = "SELECT r.id, r.text, r.created_at, r.updated_at,
                       u.username, u.login,
                       rt.score as user_rating
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                LEFT JOIN ratings rt ON r.user_id = rt.user_id AND r.film_id = rt.film_id
                WHERE r.film_id = :film_id
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':film_id' => $filmId]);
        $reviews = $stmt->fetchAll();

        return json_encode([
            'film_id' => $filmId,
            'reviews' => $reviews,
            'total' => count($reviews)
        ]);
    }

    public function updateReview($reviewId, $data) {
        if (!isset($data['text'])) {
            http_response_code(400);
            return json_encode(['error' => 'Missing required field: text']);
        }

        $text = trim($data['text']);
        if (strlen($text) < 1 || strlen($text) > 10000) {
            http_response_code(400);
            return json_encode(['error' => 'Review text must be between 1 and 10000 characters']);
        }

        try {
            $sql = "UPDATE reviews SET text = :text, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => (int)$reviewId,
                ':text' => $text,
            ]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return json_encode(['error' => 'Review not found']);
            }

            return json_encode(['message' => 'Review updated']);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(['error' => 'Failed to update review']);
        }
    }

    public function deleteReview($reviewId) {
        try {
            $sql = "DELETE FROM reviews WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$reviewId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return json_encode(['error' => 'Review not found']);
            }

            return json_encode(['message' => 'Review deleted']);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(['error' => 'Failed to delete review']);
        }
    }
}
