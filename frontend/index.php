<?php
require_once '../backend/config/database.php';

$stmt = $pdo->query("
    SELECT *
    FROM products
    WHERE status = 'aktif'
    ORDER BY id DESC
");

$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charmy - Cute Things, Happy Moments</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <header>
        <h1>Charmy</h1>
        <p>Cute Things, Happy Moments ♡</p>
        <nav>
            <a href="index.php">Beranda</a>
            <a href="#produk">Produk</a>
            <a href="#tentang">Tentang Kami</a>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h2>Make Your Day More Colorful!</h2>
            <p>Temukan ganci dan bag charm lucu favoritmu di Charmy.</p>
            <a href="#produk" class="btn">Belanja Sekarang</a>
        </section>

        <section id="produk" class="products">
            <h2>Produk Terbaru</h2>

            <div class="product-list">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <h3>
                            <?= htmlspecialchars($product['name']) ?>
                        </h3>

                        <p>
                            Rp<?= number_format($product['price'], 0, ',', '.') ?>
                        </p>

                        <button type="button">Tambah ke Keranjang</button>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($products)): ?>
                    <p>Belum ada produk tersedia.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="tentang">
            <h2>Tentang Charmy</h2>
            <p>Charmy menyediakan berbagai aksesori lucu untuk melengkapi gaya sehari-harimu.</p>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 Charmy. All Rights Reserved.</p>
    </footer>

</body>

</html>