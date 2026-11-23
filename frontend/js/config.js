// =====================================================
// config.js  -  SATU-SATUNYA tempat pengaturan API.
// Ubah file ini saja saat backend teman sudah jadi.
// =====================================================

const CONFIG = {
  // Alamat server backend (PHP). Contoh jika pakai XAMPP:
  // "http://localhost/charmy-ecommerce"
  // Kosongkan ("") jika frontend & backend satu server/satu domain.
  API_BASE_URL: "",

  // Endpoint dari tim backend (sesuai instruksi dosen)
  // Endpoint produk dari backend teman: backend/api/products/index.php
  // Path RELATIF dari frontend/katalog.html → ../backend/... (tidak tergantung nama folder di htdocs)
  PRODUCTS_ENDPOINT: "../backend/api/products/index.php",

  // Awalan untuk gambar jika field "image" hanya berisi nama file / path relatif.
  // Isi sesuai lokasi gambar sebenarnya, contoh: "../uploads/products/"
  // (isi field image di database belum terlihat di kode backend)
  IMAGE_BASE_URL: "",

  // Produk dengan stok <= angka ini ditampilkan "Sisa N" (sesuai Figma admin: <= 10)
  LOW_STOCK_THRESHOLD: 10,

  // Respons backend: { success, message, total, data: [ ... ] }  → daftar produk ada di "data".
  RESPONSE_LIST_KEY: "data",

  // ---------------------------------------------------
  // PEMETAAN FIELD - sesuai SELECT di backend/api/products/index.php
  // Field API: id, category_id, category_name, name, slug, description,
  //            price, stock, image, status
  // Field yang TIDAK ada di API diisi "" (rating, jumlah ulasan, tanggal dibuat);
  // kartu otomatis menyembunyikan rating, dan "Terbaru" diurutkan berdasarkan id.
  // ---------------------------------------------------
  FIELD_MAP: {
    id: "id",
    name: "name",
    price: "price",
    category: "category_name",
    image: "image",
    rating: "",
    reviewCount: "",
    stock: "stock",
    createdAt: ""
  }
};
