
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Gunakan metode POST"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;
$product_id = $data['product_id'] ?? null;

if (!$user_id || !$product_id) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "user_id dan product_id wajib diisi"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT id FROM carts WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);

    $cart = $stmt->fetch();

    if (!$cart) {
        echo json_encode([
            "success" => false,
            "message" => "Keranjang tidak ditemukan"
        ]);
        exit;
    }

    $stmt = $pdo->prepare(
        "DELETE FROM cart_items
         WHERE cart_id = ? AND product_id = ?"
    );

    $stmt->execute([$cart['id'], $product_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Produk berhasil dihapus dari keranjang"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Produk tidak ditemukan di keranjang"
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Gagal menghapus produk dari keranjang"
    ]);
}

?>
