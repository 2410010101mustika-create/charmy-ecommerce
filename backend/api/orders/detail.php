
<?php

require_once '../../config/database.php';

header("Content-Type: application/json");

$user_id = $_GET['user_id'] ?? null;
$order_id = $_GET['order_id'] ?? null;

if (!$user_id || !$order_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID dan Order ID harus diisi"
    ]);
    exit;
}

try {

    // Mengambil data pesanan
    $stmt = $pdo->prepare("
        SELECT *
        FROM orders
        WHERE id = :order_id
        AND user_id = :user_id
    ");

    $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $user_id
    ]);

    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("Pesanan tidak ditemukan");
    }

    // Mengambil detail produk pesanan
    $stmt = $pdo->prepare("
        SELECT
            product_id,
            product_name,
            unit_price,
            quantity,
            subtotal
        FROM order_items
        WHERE order_id = :order_id
    ");

    $stmt->execute([
        ':order_id' => $order_id
    ]);

    $items = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "message" => "Detail pesanan berhasil diambil",
        "data" => [
            "order" => $order,
            "items" => $items
        ]
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

?>
