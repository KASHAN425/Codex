<?php
require_once __DIR__ . '/db.php';

try {
    $pdo = db();
    $stmt = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name');
    json_response(['categories' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    json_response([
        'categories' => [],
        'warning' => 'Categories unavailable. Check DB connection/environment variables.',
        'details' => $e->getMessage(),
    ], 200);
}
