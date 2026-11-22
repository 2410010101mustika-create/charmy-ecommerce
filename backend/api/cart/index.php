
<?php

require_once '../../config/database.php';

header("Content-Type: application/json");

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID harus diisi"
    ]);
    exit;
}

try {
    $sql = "SELECT 
                cart_items.id,
                cart_items.product_id,
                products.name,
                products.price,
                cart_items.quantity,
                (products.price * cart_items.quantity) AS subtotal
            FROM carts
            JOIN cart_items ON carts.id = cart_items.cart_id
            JOIN products ON cart_items.product_id = products.id
            WHERE carts.user_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);

    $items = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "message" => "Data keranjang berhasil diambil",
        "data" => $items
    ], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal mengambil data keranjang"
    ]);
}

?>
