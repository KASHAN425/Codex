<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$input = get_json_input();
$name = trim($input['name'] ?? '');
$email = trim(strtolower($input['email'] ?? ''));
$password = $input['password'] ?? '';

if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    json_response(['error' => 'Provide valid name, email and password (min 6 chars).'], 422);
}

$pdo = db();
$existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$existing->execute(['email' => $email]);
if ($existing->fetch()) {
    json_response(['error' => 'Email already registered'], 409);
}

$token = bin2hex(random_bytes(24));
$stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, api_token, role) VALUES (:name, :email, :password_hash, :api_token, :role)');
$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'api_token' => $token,
    'role' => 'user',
]);

json_response([
    'message' => 'Registered successfully',
    'token' => $token,
    'user' => ['id' => (int)$pdo->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'user'],
], 201);
