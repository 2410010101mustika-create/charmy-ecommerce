
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

    $stmt = $pdo->prepare("
        SELECT
            id,
            order_code,
            subtotal,
            shipping_cost,
            discount,
            total_amount,
            payment_method,
            payment_status,
            order_status,
            ordered_at
        FROM orders
        WHERE user_id = :user_id
        ORDER BY ordered_at DESC
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $orders = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "message" => "Riwayat pesanan berhasil diambil",
        "data" => $orders
    ], JSON_PRETTY_PRINT);
} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

?>
