<?php
require_once __DIR__ . '/db.php';

$pdo = db();
$stmt = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name');
json_response(['categories' => $stmt->fetchAll()]);
