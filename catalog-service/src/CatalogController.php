<?php
class CatalogController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllFilms($genre = null, $search = null) {
        $sql = "SELECT * FROM films";
        $params = [];

        if ($genre || $search) {
            $conditions = [];
            if ($genre) {
                $conditions[] = "genre = :genre";
                $params[':genre'] = $genre;
            }
            if ($search) {
                $conditions[] = "title LIKE :search";
                $params[':search'] = "%$search%";
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $films = $stmt->fetchAll();

        return json_encode($films);
    }

    public function getFilm($filmId) {
        $sql = "SELECT * FROM films WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $filmId]);
        $film = $stmt->fetch();

        if (!$film) {
            http_response_code(404);
            return json_encode(['error' => 'Film not found']);
        }

        return json_encode($film);
    }

    public function addFilm($data) {
        $required = ['id', 'title', 'price', 'genre'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                return json_encode(['error' => "Missing required field: $field"]);
            }
        }

        if (!in_array($data['genre'], ['action', 'drama', 'comedy', 'fantasy'])) {
            http_response_code(400);
            return json_encode(['error' => 'Invalid genre']);
        }

        try {
            $sql = "INSERT INTO films (id, title, description, price, genre, img_url, video_url)
                    VALUES (:id, :title, :description, :price, :genre, :img_url, :video_url)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $data['id'],
                ':title' => $data['title'],
                ':description' => $data['description'] ?? null,
                ':price' => (int)$data['price'],
                ':genre' => $data['genre'],
                ':img_url' => $data['img_url'] ?? null,
                ':video_url' => $data['video_url'] ?? null,
            ]);

            http_response_code(201);
            return json_encode(['message' => 'Film created', 'id' => $data['id']]);
        } catch (PDOException $e) {
            http_response_code(400);
            return json_encode(['error' => 'Film ID already exists']);
        }
    }

    public function updateFilm($filmId, $data) {
        $sql = "UPDATE films SET ";
        $updates = [];
        $params = [':id' => $filmId];

        if (isset($data['title'])) {
            $updates[] = "title = :title";
            $params[':title'] = $data['title'];
        }
        if (isset($data['description'])) {
            $updates[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        if (isset($data['price'])) {
            $updates[] = "price = :price";
            $params[':price'] = (int)$data['price'];
        }
        if (isset($data['genre'])) {
            if (!in_array($data['genre'], ['action', 'drama', 'comedy', 'fantasy'])) {
                http_response_code(400);
                return json_encode(['error' => 'Invalid genre']);
            }
            $updates[] = "genre = :genre";
            $params[':genre'] = $data['genre'];
        }
        if (isset($data['img_url'])) {
            $updates[] = "img_url = :img_url";
            $params[':img_url'] = $data['img_url'];
        }

        if (empty($updates)) {
            http_response_code(400);
            return json_encode(['error' => 'No fields to update']);
        }

        $sql .= implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            return json_encode(['error' => 'Film not found']);
        }

        return json_encode(['message' => 'Film updated']);
    }

    public function deleteFilm($filmId) {
        $sql = "DELETE FROM films WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $filmId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            return json_encode(['error' => 'Film not found']);
        }

        return json_encode(['message' => 'Film deleted']);
    }
}
