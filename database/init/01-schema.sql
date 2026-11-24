-- Microservices Database Schema
-- Shared database for all services

-- Users table (user-service)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login (login),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User purchases (user-service)
CREATE TABLE IF NOT EXISTS user_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    film_id VARCHAR(50) NOT NULL,
    film_title VARCHAR(255) NOT NULL,
    price INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_film_id (film_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User views (user-service)
CREATE TABLE IF NOT EXISTS user_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    film_id VARCHAR(50) NOT NULL,
    film_title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_film_id (film_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Films table (catalog-service)
CREATE TABLE IF NOT EXISTS films (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price INT NOT NULL,
    genre VARCHAR(50) NOT NULL,
    img_url VARCHAR(500),
    video_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_genre (genre),
    INDEX idx_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ratings table (ratings-service)
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    film_id VARCHAR(50) NOT NULL,
    score INT NOT NULL CHECK (score >= 1 AND score <= 10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_film_id (film_id),
    INDEX idx_score (score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews table (ratings-service)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    film_id VARCHAR(50) NOT NULL,
    text VARCHAR(10000),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_film_id (film_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample films
INSERT INTO films (id, title, description, price, genre, img_url, video_url) VALUES
('film1', 'Film 1', 'Action movie', 299, 'action', 'assets/images/film1.jpg', 'https://www.youtube.com/embed/dQw4w9WgXcQ'),
('film2', 'Film 2', 'Action movie', 319, 'action', 'assets/images/film2.jpg', 'https://www.youtube.com/embed/jNQXAC9IVRw'),
('film3', 'Film 3', 'Action movie', 249, 'action', 'assets/images/film3.jpg', 'https://www.youtube.com/embed/M7lc1UVf-VE'),
('film4', 'Film 4', 'Action movie', 279, 'action', 'assets/images/film4.jpg', 'https://www.youtube.com/embed/3tmd-ClpJxA'),
('film5', 'Film 5', 'Action movie', 249, 'action', 'assets/images/film5.jpg', 'https://www.youtube.com/embed/QH2-TGUlwu4'),
('film6', 'Film 6', 'Drama movie', 179, 'drama', 'assets/images/film6.jpg', 'https://www.youtube.com/embed/K4TOrB7at0Y'),
('film7', 'Film 7', 'Drama movie', 299, 'drama', 'assets/images/film7.jpg', 'https://www.youtube.com/embed/ysz5S6PUM-U'),
('film8', 'Film 8', 'Drama movie', 229, 'drama', 'assets/images/film8.jpg', 'https://www.youtube.com/embed/C0DPdy98e4c'),
('film9', 'Film 9', 'Drama movie', 399, 'drama', 'assets/images/film9.jpg', 'https://www.youtube.com/embed/M4B0iioj05g'),
('film10', 'Film 10', 'Drama movie', 489, 'drama', 'assets/images/film10.jpg', 'https://www.youtube.com/embed/3dCli9yqQG4'),
('film11', 'Film 11', 'Comedy movie', 269, 'comedy', 'assets/images/film11.jpg', 'https://www.youtube.com/embed/L_jWHffIx5E'),
('film12', 'Film 12', 'Comedy movie', 199, 'comedy', 'assets/images/film12.jpg', 'https://www.youtube.com/embed/fJ9rUzIMcZQ'),
('film13', 'Film 13', 'Comedy movie', 449, 'comedy', 'assets/images/film13.jpg', 'https://www.youtube.com/embed/9bZkp7q19f0'),
('film14', 'Film 14', 'Comedy movie', 319, 'comedy', 'assets/images/film14.jpg', 'https://www.youtube.com/embed/Hl-di1dK6JM'),
('film15', 'Film 15', 'Comedy movie', 429, 'comedy', 'assets/images/film15.jpg', 'https://www.youtube.com/embed/Ny7iSLOb4PE'),
('film16', 'Film 16', 'Fantasy movie', 359, 'fantasy', 'assets/images/film16.jpg', 'https://www.youtube.com/embed/YQHsXMglC9A'),
('film17', 'Film 17', 'Fantasy movie', 239, 'fantasy', 'assets/images/film17.jpg', 'https://www.youtube.com/embed/kJQP7kiw5Fk'),
('film18', 'Film 18', 'Fantasy movie', 469, 'fantasy', 'assets/images/film18.jpg', 'https://www.youtube.com/embed/Bey4XXJAqS8'),
('film19', 'Film 19', 'Fantasy movie', 289, 'fantasy', 'assets/images/film19.jpg', 'https://www.youtube.com/embed/60ItHLz5WEA'),
('film20', 'Film 20', 'Fantasy movie', 379, 'fantasy', 'assets/images/film20.jpg', 'https://www.youtube.com/embed/YQHsXMglC9A');
