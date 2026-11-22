
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once '../../config/database.php';

try {

    $query = "SELECT
                products.id,
                products.category_id,
                categories.name AS category_name,
                products.name,
                products.slug,
                products.description,
                products.price,
                products.stock,
                products.image,
                products.status
              FROM products
              LEFT JOIN categories
              ON products.category_id = categories.id
              WHERE products.status = 'aktif'
              ORDER BY products.id ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $products = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "message" => "Data produk berhasil diambil",
        "total" => count($products),
        "data" => $products
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Gagal mengambil data produk"
    ]);
}

?>
