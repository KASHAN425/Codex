<?php
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $pdo = db();
        $search = $_GET['search'] ?? '';
        $categoryId = $_GET['category_id'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 100), 200);
        if ($limit <= 0) {
            $limit = 100;
        }

        $sql = 'SELECT p.id, p.name, p.slug, p.description, p.price, p.stock, p.image_url, p.featured, c.name AS category_name, p.category_id
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.is_active = 1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (p.name LIKE :search OR p.description LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($categoryId !== '') {
            $sql .= ' AND p.category_id = :category_id';
            $params['category_id'] = (int)$categoryId;
        }

        $sql .= ' ORDER BY p.featured DESC, p.created_at DESC LIMIT ' . $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(['products' => $stmt->fetchAll()]);
    } catch (Throwable $e) {
        json_response([
            'products' => [],
            'warning' => 'Products unavailable. Check DB connection/environment variables.',
            'details' => $e->getMessage(),
        ], 200);
    }
}

$pdo = db();

if ($method === 'POST') {
    require_admin();
    $input = get_json_input();
    $name = trim($input['name'] ?? '');
    $slug = trim($input['slug'] ?? '');
    $description = trim($input['description'] ?? '');
    $price = (float)($input['price'] ?? 0);
    $stock = (int)($input['stock'] ?? 0);
    $categoryId = (int)($input['category_id'] ?? 0);
    $imageUrl = trim($input['image_url'] ?? '');
    $featured = !empty($input['featured']) ? 1 : 0;

    if (!$name || !$slug || $price <= 0) {
        json_response(['error' => 'name, slug and valid price are required'], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO products (category_id, name, slug, description, price, stock, image_url, featured, is_active) VALUES (:category_id, :name, :slug, :description, :price, :stock, :image_url, :featured, 1)');
    $stmt->execute([
        'category_id' => $categoryId ?: null,
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
        'price' => $price,
        'stock' => max(0, $stock),
        'image_url' => $imageUrl ?: null,
        'featured' => $featured,
    ]);

    json_response(['message' => 'Product created', 'id' => (int)$pdo->lastInsertId()], 201);
}

if ($method === 'PUT') {
    require_admin();
    $input = get_json_input();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        json_response(['error' => 'Valid product id required'], 422);
    }

    $name = trim($input['name'] ?? '');
    $slug = trim($input['slug'] ?? '');
    $description = trim($input['description'] ?? '');
    $price = (float)($input['price'] ?? 0);
    $stock = (int)($input['stock'] ?? 0);
    $categoryId = (int)($input['category_id'] ?? 0);
    $imageUrl = trim($input['image_url'] ?? '');
    $featured = !empty($input['featured']) ? 1 : 0;
    $isActive = isset($input['is_active']) ? (int)!empty($input['is_active']) : 1;

    if (!$name || !$slug || $price <= 0) {
        json_response(['error' => 'name, slug and valid price are required'], 422);
    }

    $stmt = $pdo->prepare('UPDATE products SET category_id = :category_id, name = :name, slug = :slug, description = :description, price = :price, stock = :stock, image_url = :image_url, featured = :featured, is_active = :is_active WHERE id = :id');
    $stmt->execute([
        'id' => $id,
        'category_id' => $categoryId ?: null,
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
        'price' => $price,
        'stock' => max(0, $stock),
        'image_url' => $imageUrl ?: null,
        'featured' => $featured,
        'is_active' => $isActive,
    ]);

    json_response(['message' => 'Product updated']);
}

if ($method === 'DELETE') {
    require_admin();
    $input = get_json_input();
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        json_response(['error' => 'Valid product id required'], 422);
    }

    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
    json_response(['message' => 'Product deleted']);
}

json_response(['error' => 'Method not allowed'], 405);
