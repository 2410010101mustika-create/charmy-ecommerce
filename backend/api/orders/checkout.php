
<?php

require_once '../../config/database.php';

header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    echo json_encode([
        "success" => false,
        "message" => "Format JSON tidak valid"
    ]);
    exit;
}

$user_id = $input['user_id'] ?? null;
$address_id = $input['address_id'] ?? null;
$payment_method = $input['payment_method'] ?? null;
$notes = $input['notes'] ?? null;

$allowed_methods = [
    'transfer_bank',
    'e_wallet',
    'qris'
];

if (
    !$user_id ||
    !$address_id ||
    !in_array($payment_method, $allowed_methods, true)
) {
    echo json_encode([
        "success" => false,
        "message" => "Data checkout tidak lengkap atau tidak valid"
    ]);
    exit;
}

try {

    $pdo->beginTransaction();

    // 1. Mengambil alamat pengguna
    $stmt = $pdo->prepare("
        SELECT *
        FROM addresses
        WHERE id = :address_id
        AND user_id = :user_id
    ");

    $stmt->execute([
        ':address_id' => $address_id,
        ':user_id' => $user_id
    ]);

    $address = $stmt->fetch();

    if (!$address) {
        throw new Exception("Alamat pengguna tidak ditemukan");
    }

    // 2. Mengambil produk dari keranjang
    $stmt = $pdo->prepare("
        SELECT
            ci.product_id,
            ci.quantity,
            p.name,
            p.price
        FROM carts c
        JOIN cart_items ci ON c.id = ci.cart_id
        JOIN products p ON ci.product_id = p.id
        WHERE c.user_id = :user_id
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $items = $stmt->fetchAll();

    if (empty($items)) {
        throw new Exception("Keranjang masih kosong");
    }

    // 3. Menghitung subtotal
    $subtotal = 0;

    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    $shipping_cost = 0;
    $discount = 0;

    $total_amount = $subtotal + $shipping_cost - $discount;

    // 4. Membuat alamat pengiriman
    $shipping_address =
        $address['address_line'] . ", " .
        $address['city'] . ", " .
        $address['province'] . ", " .
        $address['postal_code'];

    // 5. Membuat kode pesanan
    $order_code = "CHM-" . date("YmdHis") . "-" . random_int(100, 999);

    // 6. Menyimpan pesanan
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_code,
            user_id,
            address_id,
            shipping_name,
            shipping_phone,
            shipping_address,
            subtotal,
            shipping_cost,
            discount,
            payment_method,
            payment_status,
            order_status,
            notes
        ) VALUES (
            :order_code,
            :user_id,
            :address_id,
            :shipping_name,
            :shipping_phone,
            :shipping_address,
            :subtotal,
            :shipping_cost,
            :discount,
            :payment_method,
            'menunggu_pembayaran',
            'menunggu_pembayaran',
            :notes
        )
    ");

    $stmt->execute([
        ':order_code' => $order_code,
        ':user_id' => $user_id,
        ':address_id' => $address_id,
        ':shipping_name' => $address['recipient_name'],
        ':shipping_phone' => $address['phone'],
        ':shipping_address' => $shipping_address,
        ':subtotal' => $subtotal,
        ':shipping_cost' => $shipping_cost,
        ':discount' => $discount,
        ':payment_method' => $payment_method,
        ':notes' => $notes
    ]);

    $order_id = $pdo->lastInsertId();

    // 7. Menyimpan detail produk pesanan
    // Kolom subtotal tidak dimasukkan karena dihitung otomatis oleh database

    $stmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            unit_price,
            quantity
        ) VALUES (
            :order_id,
            :product_id,
            :product_name,
            :unit_price,
            :quantity
        )
    ");

    foreach ($items as $item) {

        $stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['product_id'],
            ':product_name' => $item['name'],
            ':unit_price' => $item['price'],
            ':quantity' => $item['quantity']
        ]);
    }

    // 8. Menyimpan data pembayaran
    $stmt = $pdo->prepare("
        INSERT INTO payments (
            order_id,
            method,
            amount,
            status
        ) VALUES (
            :order_id,
            :method,
            :amount,
            'menunggu_pembayaran'
        )
    ");

    $stmt->execute([
        ':order_id' => $order_id,
        ':method' => $payment_method,
        ':amount' => $total_amount
    ]);

    // 9. Mengosongkan keranjang setelah checkout berhasil
    $stmt = $pdo->prepare("
        DELETE ci
        FROM cart_items ci
        INNER JOIN carts c ON ci.cart_id = c.id
        WHERE c.user_id = :user_id
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    // 10. Menyelesaikan transaksi
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Checkout berhasil",
        "data" => [
            "order_id" => $order_id,
            "order_code" => $order_code,
            "subtotal" => $subtotal,
            "shipping_cost" => $shipping_cost,
            "discount" => $discount,
            "total_amount" => $total_amount,
            "payment_method" => $payment_method,
            "payment_status" => "menunggu_pembayaran"
        ]
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

?>
