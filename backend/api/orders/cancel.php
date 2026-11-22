
<?php
header("Content-Type: application/json");
require_once "../../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data["user_id"] ?? null;
$order_id = $data["order_id"] ?? null;

if (!$user_id || !$order_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID dan Order ID wajib diisi"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, order_status, payment_status
        FROM orders
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode([
            "success" => false,
            "message" => "Pesanan tidak ditemukan"
        ]);
        exit;
    }

    if ($order["order_status"] !== "menunggu_pembayaran") {
        echo json_encode([
            "success" => false,
            "message" => "Pesanan tidak dapat dibatalkan"
        ]);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE orders
        SET order_status = 'dibatalkan'
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);

    $stmt = $pdo->prepare("
        UPDATE payments
        SET status = 'dibatalkan'
        WHERE order_id = ?
    ");
    $stmt->execute([$order_id]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Pesanan berhasil dibatalkan"
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "success" => false,
        "message" => "Terjadi kesalahan: " . $e->getMessage()
    ]);
}
?>
