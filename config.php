<?php
$DB_DSN = 'mysql:host=127.0.0.1;dbname=sports_scoring_system;charset=utf8mb4';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo "<h1>Database connection error</h1><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}