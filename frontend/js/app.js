// =====================================================
// app.js  -  Logika halaman: tampil, cari, filter, urut.
// =====================================================

const state = { products: [], category: "all", query: "", sort: "newest" };

const $grid = document.getElementById("productGrid");
const $status = document.getElementById("status");
const $search = document.getElementById("searchInput");
const $headerSearch = document.getElementById("headerSearch");
const $sort = document.getElementById("sortSelect");
const $chips = document.getElementById("categoryChips");
const $info = document.getElementById("resultInfo");
const $infoText = document.getElementById("resultText");
const $clearCat = document.getElementById("clearCategory");
const $more = document.getElementById("moreSection");
const $moreGrid = document.getElementById("moreGrid");

// ---------- Helper ----------
const rupiah = (n) => "Rp " + Number(n).toLocaleString("id-ID");
const norm = (s) => String(s ?? "").trim().toLowerCase();
function esc(s) {
  const d = document.createElement("div");
  d.textContent = s ?? "";
  return d.innerHTML;
}

// ---------- Tampilan status ----------
function showStatus(type, message, withRetry) {
  $grid.innerHTML = "";
  $info.hidden = true;
  $more.hidden = true;
  $status.hidden = false;
  $status.className = "status status--" + type;
  $status.innerHTML = (type === "loading" ? '<div class="spinner"></div>' : "") +
    "<p>" + esc(message) + "</p>" +
    (withRetry ? '<button class="btn" id="retryBtn">Coba Lagi</button>' : "");
  if (withRetry) document.getElementById("retryBtn").onclick = loadProducts;
}

// ---------- Kartu produk ----------
function stockBadge(p) {
  if (p.stock == null) return "";
  if (p.stock <= 0) return '<span class="badge badge--out">Habis</span>';
  if (p.stock <= CONFIG.LOW_STOCK_THRESHOLD) return '<span class="badge badge--low">Sisa ' + p.stock + "</span>";
  return '<span class="badge badge--ready">Ready stock</span>';
}

function ratingHtml(p) {
  if (p.rating == null || isNaN(p.rating)) return "<span></span>";
  const pct = Math.max(0, Math.min(100, (p.rating / 5) * 100));
  const count = p.reviewCount != null ? " (" + esc(p.reviewCount) + ")" : "";
  return '<span class="rating"><span class="stars" style="--pct:' + pct + '%">★★★★★</span> ' +
    p.rating.toFixed(1) + count + "</span>";
}

function cardHtml(p) {
  const img = p.image || "assets/img/placeholder.svg";
  return '<article class="card">' +
    '<div class="card__img"><img src="' + esc(img) + '" alt="' + esc(p.name) + '" loading="lazy" ' +
    'onerror="this.onerror=null;this.src=\'assets/img/placeholder.svg\'">' +
    '<button class="fav" aria-label="Wishlist"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.6-9.3-9A5.3 5.3 0 0 1 12 6a5.3 5.3 0 0 1 9.3 6c-2.3 4.4-9.3 9-9.3 9z"/></svg></button></div>' +
    '<h3 class="card__name">' + esc(p.name) + "</h3>" +
    '<div class="card__meta">' + ratingHtml(p) + stockBadge(p) + "</div>" +
    '<div class="card__bottom"><span class="price">' + (isNaN(p.price) ? "-" : rupiah(p.price)) + "</span>" +
    '<button class="cart" aria-label="Tambah ke keranjang"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h2l2.5 11h10l2-8H6.5"/><circle cx="9" cy="19.5" r="1"/><circle cx="17" cy="19.5" r="1"/></svg></button></div>' +
    "</article>";
}

// ---------- Filter + cari + urut (semua pada data dari API) ----------
function getVisibleProducts() {
  const q = norm(state.query);
  let list = state.products.filter((p) => {
    const okCat = state.category === "all" || norm(p.category) === norm(state.category);
    const okQ = !q || norm(p.name).includes(q) || norm(p.category).includes(q);
    return okCat && okQ;
  });

  const by = {
    "price-asc": (a, b) => a.price - b.price,
    "price-desc": (a, b) => b.price - a.price,
    rating: (a, b) => (b.rating ?? 0) - (a.rating ?? 0),
    name: (a, b) => a.name.localeCompare(b.name, "id"),
    newest: (a, b) => {
      if (a.createdAt && b.createdAt) return new Date(b.createdAt) - new Date(a.createdAt);
      return Number(b.id) - Number(a.id); // cadangan: id terbesar = terbaru
    }
  };
  return list.sort(by[state.sort]);
}

function render() {
  const list = getVisibleProducts();
  const filtering = state.query.trim() || state.category !== "all";

  // Info hasil (sesuai Figma halaman 4)
  $info.hidden = !filtering;
  if (filtering) {
    $infoText.textContent = "Menampilkan " + list.length + " produk" +
      (state.query.trim() ? ' untuk “' + state.query.trim() + '”' : "");
    $clearCat.hidden = state.category === "all";
    // Tampilkan label tombol yang aktif (mis. "Ganci"), bukan nilai kategori di database ("Keychain")
    $clearCat.textContent = "Kategori: " + $chips.querySelector(".chip.active").textContent + " ×";
  }

  if (state.products.length === 0) return showStatus("empty", "Belum ada produk yang tersedia.");
  if (list.length === 0) {
    showStatus("empty", "Produk tidak ditemukan. Coba kata kunci atau kategori lain.");
    $info.hidden = !filtering;
    renderMore(list, filtering);
    return;
  }
  $status.hidden = true;
  $grid.innerHTML = list.map(cardHtml).join("");
  renderMore(list, filtering);
}

// ---------- "Produk Lainnya" (Figma halaman 4) ----------
// Saat pencarian/filter aktif, tampilkan produk lain yang tidak termasuk hasil.
function renderMore(list, filtering) {
  const others = filtering ? state.products.filter((p) => !list.includes(p)).slice(0, 3) : [];
  $more.hidden = others.length === 0;
  $moreGrid.innerHTML = others.map(cardHtml).join("");
}

// ---------- Ambil data ----------
async function loadProducts() {
  showStatus("loading", "Memuat produk...");
  try {
    state.products = await fetchProducts();
    render();
  } catch (err) {
    console.error(err);
    showStatus("error", "Gagal memuat produk. " + err.message, true);
  }
}

// ---------- Event ----------
$search.addEventListener("input", () => {
  state.query = $search.value; $headerSearch.value = $search.value; render();
});
$headerSearch.addEventListener("input", () => {
  state.query = $headerSearch.value; $search.value = $headerSearch.value; render();
});
$sort.addEventListener("change", () => { state.sort = $sort.value; render(); });
$chips.addEventListener("click", (e) => {
  const btn = e.target.closest(".chip");
  if (!btn) return;
  state.category = btn.dataset.category;
  $chips.querySelectorAll(".chip").forEach((c) => c.classList.toggle("active", c === btn));
  render();
});
$clearCat.addEventListener("click", () => {
  state.category = "all";
  $chips.querySelectorAll(".chip").forEach((c) => c.classList.toggle("active", c.dataset.category === "all"));
  render();
});
document.getElementById("menuToggle").addEventListener("click", () => {
  document.getElementById("nav").classList.toggle("open");
});

loadProducts();
