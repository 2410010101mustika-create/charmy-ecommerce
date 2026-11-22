
<?php

require_once '../../config/database.php';

header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

$user_id = $input['user_id'] ?? null;
$product_id = $input['product_id'] ?? null;
$quantity = $input['quantity'] ?? null;

if (!$user_id || !$product_id || !$quantity || $quantity < 1) {
    echo json_encode([
        "success" => false,
        "message" => "User ID, Product ID, dan Quantity harus diisi dengan benar"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();

    if (!$cart) {
        echo json_encode([
            "success" => false,
            "message" => "Keranjang tidak ditemukan"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE cart_items
        SET quantity = ?
        WHERE cart_id = ? AND product_id = ?
    ");

    $stmt->execute([$quantity, $cart['id'], $product_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Produk tidak ditemukan atau jumlah tidak berubah"
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Jumlah produk berhasil diperbarui"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal memperbarui keranjang"
    ]);
}

?>
