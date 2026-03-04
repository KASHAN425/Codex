<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$user = require_auth_user();
if (($user['role'] ?? 'user') !== 'user') {
    json_response(['error' => 'Only user accounts can place orders'], 403);
}
$input = get_json_input();
$items = $input['items'] ?? [];
$shippingAddress = trim($input['shipping_address'] ?? '');
$phone = trim($input['phone'] ?? '');

if (!$shippingAddress || !$phone || !is_array($items) || count($items) === 0) {
    json_response(['error' => 'Shipping address, phone and cart items are required.'], 422);
}

$pdo = db();
$total = 0;
$orderLines = [];

foreach ($items as $item) {
    $productId = (int)($item['product_id'] ?? 0);
    $quantity = max(1, (int)($item['quantity'] ?? 1));

    $stmt = $pdo->prepare('SELECT id, name, price, stock FROM products WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        json_response(['error' => "Product ID {$productId} not found."], 404);
    }

    if ((int)$product['stock'] < $quantity) {
        json_response(['error' => "Insufficient stock for {$product['name']}."], 409);
    }

    $lineTotal = $quantity * (float)$product['price'];
    $total += $lineTotal;

    $orderLines[] = [
        'product_id' => (int)$product['id'],
        'product_name' => $product['name'],
        'price' => (float)$product['price'],
        'quantity' => $quantity,
        'line_total' => $lineTotal,
    ];
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO orders (user_id, total_amount, shipping_address, phone, status) VALUES (:user_id, :total_amount, :shipping_address, :phone, :status)');
    $stmt->execute([
        'user_id' => $user['id'],
        'total_amount' => $total,
        'shipping_address' => $shippingAddress,
        'phone' => $phone,
        'status' => 'pending',
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity, :line_total)');
    $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :product_id');

    foreach ($orderLines as $line) {
        $itemStmt->execute([
            'order_id' => $orderId,
            'product_id' => $line['product_id'],
            'product_name' => $line['product_name'],
            'unit_price' => $line['price'],
            'quantity' => $line['quantity'],
            'line_total' => $line['line_total'],
        ]);

        $stockStmt->execute([
            'quantity' => $line['quantity'],
            'product_id' => $line['product_id'],
        ]);
    }

    $pdo->commit();
    json_response(['message' => 'Order placed successfully', 'order_id' => $orderId, 'total_amount' => $total], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Could not place order', 'details' => $e->getMessage()], 500);
}
