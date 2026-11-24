<?php
class Database {
    private $pdo;

    public function __construct() {
        $host = getenv('DB_HOST') ?: 'mysql';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'microservices_db';
        $user = getenv('DB_USER') ?: 'app_user';
        $password = getenv('DB_PASSWORD') ?: 'app_password';

        try {
            $this->pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}
