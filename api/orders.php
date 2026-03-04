<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$user = require_auth_user();
$pdo = db();

$stmt = $pdo->prepare('SELECT id, total_amount, shipping_address, phone, status, created_at FROM orders WHERE user_id = :user_id ORDER BY created_at DESC');
$stmt->execute(['user_id' => $user['id']]);
$orders = $stmt->fetchAll();

$itemStmt = $pdo->prepare('SELECT product_name, unit_price, quantity, line_total FROM order_items WHERE order_id = :order_id');
foreach ($orders as &$order) {
    $itemStmt->execute(['order_id' => $order['id']]);
    $order['items'] = $itemStmt->fetchAll();
}

json_response(['orders' => $orders]);
