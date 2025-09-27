<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Go one directory up (from config → backend)
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Access environment variables
$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$db   = $_ENV['DB_NAME'] ?? 'ecc';

// Create DB connection
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
