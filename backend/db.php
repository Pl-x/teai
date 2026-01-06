<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$servername = $_ENV['POSTGRES_HOST'];
$username = $_ENV['POSTGRES_USER'];
$password = $_ENV['POSTGRES_PASSWORD'];
$database = $_ENV['POSTGRES_DB'];
$port = $_ENV['POSTGRES_PORT'];

try {
    $dsn = "pgsql:host=$servername;port=$port;dbname=$database";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
try {
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        user_id SERIAL PRIMARY KEY,
        uuid char(36) NOT NULL DEFAULT gen_random_uuid() UNIQUE,
        name VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        country VARCHAR(50),
        language VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    $sql2 = "
    CREATE TABLE IF NOT EXISTS tests (
        test_id SERIAL PRIMARY KEY,
        user_id INT NOT NULL,
        test_name VARCHAR(100) NOT NULL,
        score INT,
        taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $pdo->exec($sql2);
    $sql3 = "
    CREATE TABLE IF NOT EXISTS sessions (
        session_id SERIAL PRIMARY KEY,
        user_id INT NOT NULL,
        session_token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $pdo->exec($sql3);
    $sql4 = "
    CREATE TABLE IF NOT EXISTS predictions (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL,
        image_path TEXT NOT NULL,
        disease VARCHAR(255),
        confidence REAL,
        probabilities JSONB,
        solution TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $pdo->exec($sql4);

} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
?>
