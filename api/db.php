<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function json_response($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function get_json_input()
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_response(['error' => 'Invalid JSON body'], 400);
    }

    return $decoded;
}

function db()
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASSWORD');

    if (!$host || !$name || !$user) {
        json_response([
            'error' => 'Missing database environment variables. Set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD.'
        ], 500);
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        json_response(['error' => 'Database connection failed', 'details' => $e->getMessage()], 500);
    }
}

function get_bearer_token()
{
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return null;
    }

    if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
        return $matches[1];
    }

    return null;
}

function require_auth_user()
{
    $token = get_bearer_token();
    if (!$token) {
        json_response(['error' => 'Authorization token required'], 401);
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE api_token = :token LIMIT 1');
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(['error' => 'Invalid token'], 401);
    }

    return $user;
}

function require_admin()
{
    $user = require_auth_user();
    if (($user['role'] ?? 'user') !== 'admin') {
        json_response(['error' => 'Admin access required'], 403);
    }
    return $user;
}
