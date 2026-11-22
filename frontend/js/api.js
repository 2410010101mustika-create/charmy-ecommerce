// =====================================================
// api.js  -  Mengambil data dari backend dengan fetch().
// Tidak berisi data produk apa pun.
// =====================================================

async function fetchProducts() {
  const url = CONFIG.API_BASE_URL + CONFIG.PRODUCTS_ENDPOINT;

  const response = await fetch(url, { headers: { Accept: "application/json" } });
  if (!response.ok) {
    throw new Error("Server menjawab status " + response.status);
  }

  const json = await response.json();

  // Backend mengirim { success, message, total, data }
  if (json.success === false) {
    throw new Error(json.message || "API mengembalikan success=false");
  }

  // Ambil daftar produk dari JSON (array langsung, atau di dalam key tertentu)
  const list = CONFIG.RESPONSE_LIST_KEY ? json[CONFIG.RESPONSE_LIST_KEY] : json;
  if (!Array.isArray(list)) {
    console.error("Respons API:", json);
    throw new Error("Format respons bukan daftar produk. Cek RESPONSE_LIST_KEY di config.js");
  }

  if (list.length > 0) {
    console.log("Contoh 1 produk dari API (cocokkan dengan FIELD_MAP):", list[0]);
  }
  return list.map(normalizeProduct);
}

// Mengubah 1 produk dari bentuk backend → bentuk standar frontend
function normalizeProduct(raw) {
  const m = CONFIG.FIELD_MAP;
  const img = raw[m.image];
  const stock = raw[m.stock];
  const rating = raw[m.rating];

  return {
    id: raw[m.id],
    name: raw[m.name] ?? "",
    price: Number(raw[m.price]),
    category: raw[m.category] ?? "",
    image: img ? (/^https?:\/\//.test(img) ? img : CONFIG.IMAGE_BASE_URL + img) : "",
    rating: rating != null ? Number(rating) : null,
    reviewCount: raw[m.reviewCount] ?? null,
    stock: stock != null ? Number(stock) : null,
    createdAt: raw[m.createdAt] ?? null
  };
}
