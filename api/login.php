<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$input = get_json_input();
$email = trim(strtolower($input['email'] ?? ''));
$password = $input['password'] ?? '';
$loginType = strtolower(trim($input['login_type'] ?? 'user'));

if (!in_array($loginType, ['user', 'admin'], true)) {
    json_response(['error' => 'login_type must be user or admin'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$password) {
    json_response(['error' => 'Invalid credentials'], 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, name, email, role, password_hash FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['error' => 'Email or password is incorrect'], 401);
}

if ($loginType === 'admin' && $user['role'] !== 'admin') {
    json_response(['error' => 'This account is not an admin account'], 403);
}

if ($loginType === 'user' && $user['role'] !== 'user') {
    json_response(['error' => 'Please use admin login for admin account'], 403);
}

$token = bin2hex(random_bytes(24));
$pdo->prepare('UPDATE users SET api_token = :token WHERE id = :id')->execute([
    'token' => $token,
    'id' => $user['id'],
]);

json_response([
    'message' => 'Login successful',
    'token' => $token,
    'user' => ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
]);
