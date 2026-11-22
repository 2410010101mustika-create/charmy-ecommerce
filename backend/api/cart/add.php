
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Gunakan metode POST"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? 0;
$product_id = $data['product_id'] ?? 0;
$quantity = $data['quantity'] ?? 1;

if ($user_id <= 0 || $product_id <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Data tidak lengkap atau tidak valid"
    ]);
    exit;
}

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT id FROM users WHERE id = ? AND role = 'customer'"
    );
    $stmt->execute([$user_id]);

    if (!$stmt->fetch()) {
        throw new Exception("Pengguna tidak ditemukan");
    }

    $stmt = $pdo->prepare(
        "SELECT id, stock FROM products
         WHERE id = ? AND status = 'aktif' FOR UPDATE"
    );
    $stmt->execute([$product_id]);

    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception("Produk tidak ditemukan");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO carts (user_id)
         VALUES (?)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
    );
    $stmt->execute([$user_id]);

    $cart_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare(
        "SELECT quantity FROM cart_items
         WHERE cart_id = ? AND product_id = ?"
    );
    $stmt->execute([$cart_id, $product_id]);

    $item = $stmt->fetch();

    $current_quantity = $item ? (int)$item['quantity'] : 0;
    $new_quantity = $current_quantity + $quantity;

    if ($new_quantity > (int)$product['stock']) {
        throw new Exception("Jumlah melebihi stok produk");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO cart_items (cart_id, product_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)"
    );

    $stmt->execute([$cart_id, $product_id, $quantity]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Produk berhasil ditambahkan ke keranjang"
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

?>
