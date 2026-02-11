import api from "./axios";
import { formatCurrency, formatDateTime, formatPaymentMethod } from "./format";
import { formatIntegerOrDecimal } from "./format";

const PRINTER_SETTINGS_KEY = "pos_printer_settings";

const DEFAULT_PRINTER_SETTINGS = {
  paperWidth: 80,
  fontSize: "normal",
  showStoreHeader: true,
  defaultPrinterName: "XP58",
};

function normalizePrinterSettings(raw) {
  if (!raw) return { ...DEFAULT_PRINTER_SETTINGS };
  return {
    paperWidth: [58, 80].includes(Number(raw.paperWidth ?? raw.paper_width))
      ? Number(raw.paperWidth ?? raw.paper_width)
      : DEFAULT_PRINTER_SETTINGS.paperWidth,
    fontSize: ["small", "normal", "large"].includes(
      raw.fontSize ?? raw.font_size,
    )
      ? (raw.fontSize ?? raw.font_size)
      : DEFAULT_PRINTER_SETTINGS.fontSize,
    showStoreHeader:
      typeof (raw.showStoreHeader ?? raw.show_store_header) === "boolean"
        ? (raw.showStoreHeader ?? raw.show_store_header)
        : DEFAULT_PRINTER_SETTINGS.showStoreHeader,
    defaultPrinterName:
      typeof (raw.defaultPrinterName ?? raw.default_printer_name) ===
        "string" && (raw.defaultPrinterName ?? raw.default_printer_name).trim()
        ? (raw.defaultPrinterName ?? raw.default_printer_name).trim()
        : null,
  };
}

/** Ambil pengaturan printer dari API (database), fallback ke localStorage lalu default */
export async function getPrinterSettings() {
  if (typeof window === "undefined") return { ...DEFAULT_PRINTER_SETTINGS };
  try {
    const res = await api.get("/store");
    if (
      res.data &&
      (res.data.paper_width != null || res.data.paperWidth != null)
    ) {
      return normalizePrinterSettings(res.data);
    }
  } catch (_) {}
  try {
    const raw = localStorage.getItem(PRINTER_SETTINGS_KEY);
    if (raw) return normalizePrinterSettings(JSON.parse(raw));
  } catch (_) {}
  return { ...DEFAULT_PRINTER_SETTINGS };
}

/** Simpan pengaturan printer ke database (API) */
export async function savePrinterSettings(settings) {
  if (typeof window === "undefined") return;
  await api.put("/store", {
    paper_width: settings.paperWidth ?? DEFAULT_PRINTER_SETTINGS.paperWidth,
    font_size: settings.fontSize ?? DEFAULT_PRINTER_SETTINGS.fontSize,
    show_store_header:
      settings.showStoreHeader ?? DEFAULT_PRINTER_SETTINGS.showStoreHeader,
    default_printer_name:
      settings.defaultPrinterName ??
      DEFAULT_PRINTER_SETTINGS.defaultPrinterName,
  });
}

/** Cek apakah jalan di Tauri (desktop app) */
export function isTauri() {
  return typeof window !== "undefined" && window.__TAURI__;
}

/**
 * Scan / daftar printer yang terdeteksi di sistem (hanya di Tauri desktop)
 * @returns {Promise<string[]>} Daftar nama printer
 */
export async function getSystemPrinters() {
  if (!isTauri()) {
    return [];
  }
  try {
    const { invoke } = await import("@tauri-apps/api/core");
    const list = await invoke("get_system_printers");
    console.log("list", invoke);
    return Array.isArray(list) ? list : [];
  } catch (e) {
    console.warn("get_system_printers failed:", e);
    throw e;
  }
}

/**
 * Cetak struk via backend (Laravel) - printer terhubung ke server
 * Tetap dipakai untuk browser / saat tidak di desktop
 */
export async function printReceiptViaBackend(sale) {
  const id = sale?.id;
  if (!id) throw new Error("ID transaksi tidak valid");
  const res = await api.post(`/sales/${id}/print-receipt`);
  return { success: true, message: res.data?.message };
}

/**
 * Bangun HTML struk dari data sale (format mirip Laravel ReceiptPrintService)
 * @param {Object} options - dari getPrinterSettings(): paperWidth (58|80), fontSize (small|normal|large), showStoreHeader (boolean)
 */
function buildReceiptHtml(sale, store = {}, options = {}) {
  const opts = { ...DEFAULT_PRINTER_SETTINGS, ...options };
  const widthPx = opts.paperWidth === 58 ? 220 : 280;
  const fontSizes = { small: 10, normal: 12, large: 14 };
  const fontSize = fontSizes[opts.fontSize] ?? 12;
  const fontSizeSmall = Math.max(9, fontSize - 2);

  const storeName = (store.name || "POS System").substring(0, 24);
  const storeAddress = (store.address || "").trim().substring(0, 48);
  const storePhone = (store.phone || "").trim();
  const invoice = sale.invoice_number || "-";
  const dateStr = formatDateTime(sale.sale_date || sale.created_at);
  const cashier = (sale.user?.name || "-").substring(0, 24);
  const details = sale.details || [];
  const subtotalVal = Number(sale.subtotal || 0);
  const discountVal = Number(sale.discount || 0);
  const taxVal = Number(sale.tax || 0);
  const totalVal = Number(sale.total || 0);
  const paidVal = Number(sale.paid || 0);
  const changeVal = Number(sale.change || 0);
  const paymentMethod = formatPaymentMethod(sale.payment_method);
  const showHeader = opts.showStoreHeader !== false;

  let itemsHtml = details
    .map((d) => {
      const name = (d.product?.name || "Produk").substring(0, 24);
      const qtyStr = formatIntegerOrDecimal(d.quantity);
      const unit = d.unit?.name || "";
      const price = formatCurrency(d.price);
      const subtotal = formatCurrency(d.subtotal);
      const itemDiscount =
        Number(d.price) * Number(d.quantity) - Number(d.subtotal);
      const discountLine =
        itemDiscount > 0.001
          ? `<div class="item-discount">Diskon: -${formatCurrency(itemDiscount)}</div>`
          : "";
      return `
        <div class="item">
          <div class="item-name">${escapeHtml(name)}</div>
          <div class="item-line">${escapeHtml(qtyStr)} ${escapeHtml(unit)} x ${price}</div>
          ${discountLine}
          <div class="item-subtotal">${subtotal}</div>
        </div>`;
    })
    .join("");

  const changeLine =
    changeVal > 0
      ? `<div class="row"><span>Kembalian</span><span>${formatCurrency(changeVal)}</span></div>`
      : "";
  const sisaUtangLine =
    sale.payment_method === "credit" && paidVal < totalVal
      ? `<div class="row"><span>Sisa Utang</span><span>${formatCurrency(totalVal - paidVal)}</span></div>`
      : "";
  const customerNameLine = sale.customer_name
    ? `<div class="row-text">Pelanggan: ${escapeHtml(sale.customer_name)}</div>`
    : "";
  const customerPhoneLine = sale.customer_phone
    ? `<div class="row-text">No. HP: ${escapeHtml(sale.customer_phone)}</div>`
    : "";

  const headerBlock = showHeader
    ? `
    <div class="center store-name">${escapeHtml(storeName)}</div>
    ${storeAddress ? `<div class="center store-address">${escapeHtml(storeAddress)}</div>` : ""}
    ${storePhone ? `<div class="center store-address">${escapeHtml(storePhone)}</div>` : ""}
    `
    : "";

  return `
  <style>
    .receipt-body * { margin: 0; padding: 0; box-sizing: border-box; }
    .receipt-body { font-family: monospace; font-size: ${fontSize}px; line-height: 1.35; padding: 12px; max-width: ${widthPx}px; }
    .receipt-body .center { text-align: center; }
    .receipt-body .store-name { font-weight: bold; margin-bottom: 2px; }
    .receipt-body .store-address { font-size: ${fontSizeSmall}px; color: #333; margin-bottom: 4px; }
    .receipt-body .header { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 6px 0; margin: 8px 0; text-align: center; font-weight: bold; }
    .receipt-body .meta { margin-bottom: 6px; }
    .receipt-body .section { border-bottom: 1px dashed #ccc; padding: 4px 0; margin: 4px 0; }
    .receipt-body .item { margin: 4px 0; }
    .receipt-body .item-name { font-weight: bold; }
    .receipt-body .item-line { font-size: ${fontSizeSmall}px; }
    .receipt-body .item-discount { font-size: ${fontSizeSmall}px; color: #666; }
    .receipt-body .item-subtotal { text-align: right; }
    .receipt-body .row { display: flex; justify-content: space-between; margin: 2px 0; }
    .receipt-body .row-text { margin: 2px 0; }
    .receipt-body .footer { border-top: 1px dashed #000; padding-top: 8px; margin-top: 8px; text-align: center; font-size: ${fontSizeSmall}px; }
  </style>
  <div class="receipt-body">
    ${headerBlock}
    <div class="header">STRUK PEMBAYARAN</div>
    <div class="meta">
      <div>Invoice: ${escapeHtml(invoice)}</div>
      <div>Tanggal: ${escapeHtml(dateStr)}</div>
      <div>Kasir  : ${escapeHtml(cashier)}</div>
    </div>
    <div class="section">
      <div class="center" style="margin-bottom:4px">ITEM</div>
      ${itemsHtml}
    </div>
    <div class="section">
      <div class="row"><span>Subtotal</span><span>${formatCurrency(subtotalVal)}</span></div>
      ${discountVal > 0 ? `<div class="row"><span>Diskon</span><span>-${formatCurrency(discountVal)}</span></div>` : ""}
      ${taxVal > 0 ? `<div class="row"><span>Pajak</span><span>${formatCurrency(taxVal)}</span></div>` : ""}
      <div class="row"><span>TOTAL</span><span>${formatCurrency(totalVal)}</span></div>
      <div class="row"><span>Bayar</span><span>${formatCurrency(paidVal)}</span></div>
      ${changeLine}
      ${sisaUtangLine}
      <div class="row-text">Metode: ${escapeHtml(paymentMethod)}</div>
      ${customerNameLine}
      ${customerPhoneLine}
    </div>
    <div class="footer">
      ========================<br>
      Terima kasih atas<br>
      kunjungan Anda<br>
      ========================
    </div>
  </div>`;
}

function escapeHtml(text) {
  if (text == null) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Bangun isi struk plain text dengan kode ESC/POS untuk printer thermal.
 * (Versi ini sudah disesuaikan/diperbaiki: pakai ESC/POS, lineWidth, alignment, FEED_TOP.)
 * @param {Object} sale - data penjualan
 * @param {Object} store - data toko
 * @param {Object} options - { lineWidth?: number } (default 32)
 */
function buildReceiptText(sale, store = {}, options = {}) {
  /* ================= ESC / POS ================= */
  const ESC = "\x1B";

  const INIT_PRINTER = ESC + "@";
  const NORMAL_FONT = ESC + "!" + "\x00";
  const ALIGN_LEFT = ESC + "a" + "\x00";
  const ALIGN_CENTER = ESC + "a" + "\x01";
  const FEED_TOP = ESC + "d" + "\x04"; // <-- 2 baris padding atas

  /* ================= SETTING ================= */
  const LINE_WIDTH = options.paperWidth == 58 ? 32 : 48;

  const line = (char = "=") => char.repeat(LINE_WIDTH);

  /* ================= DATA ================= */
  const storeName = store.name || "POS System";
  const storeAddress = (store.address || "").trim();
  const storePhone = (store.phone || "").trim();

  const invoice = sale.invoice_number || "-";
  const dateStr = formatDateTime(sale.sale_date || sale.created_at);
  const cashier = sale.user?.name || "-";

  const details = sale.details || [];

  const subtotalVal = Number(sale.subtotal || 0);
  const discountVal = Number(sale.discount || 0);
  const taxVal = Number(sale.tax || 0);
  const totalVal = Number(sale.total || 0);
  const paidVal = Number(sale.paid || 0);
  const changeVal = Number(sale.change || 0);

  const paymentMethod = formatPaymentMethod(sale.payment_method);

  /* ================= BUILD ================= */
  const lines = [];

  // ===== HEADER =====
  lines.push(ALIGN_CENTER + storeName);
  if (storeAddress) lines.push(storeAddress);
  if (storePhone) lines.push(storePhone);

  lines.push(line("="));
  lines.push("STRUK PEMBAYARAN");
  lines.push(line("="));
  lines.push("");

  // ===== INFO =====
  lines.push(ALIGN_LEFT + `Invoice   : ${invoice}`);
  lines.push(`Tanggal   : ${dateStr}`);
  lines.push(`Kasir     : ${cashier}`);
  if (sale.customer_name)
    lines.push(`Pelanggan : ${(sale.customer_name || "").trim()}`);
  if (sale.customer_phone)
    lines.push(`No. HP    : ${(sale.customer_phone || "").trim()}`);
  if (sale.customer_address)
    lines.push(`Alamat    : ${(sale.customer_address || "").trim()}`);
  lines.push("");

  // ===== ITEMS =====
  lines.push(line("-"));
  lines.push("ITEM");
  lines.push(line("-"));

  for (const d of details) {
    const name = d.product?.name || "Produk";
    const qtyStr = formatIntegerOrDecimal(d.quantity);
    const unit = d.unit?.name || "";
    const price = formatCurrency(d.price);
    const subtotal = formatCurrency(d.subtotal);

    lines.push(name);
    lines.push(`${qtyStr} ${unit} x ${price}`);
    lines.push(`${" ".repeat(LINE_WIDTH - subtotal.length)}${subtotal}`);
  }

  // ===== TOTAL =====
  lines.push(line("-"));
  lines.push(`Subtotal : ${formatCurrency(subtotalVal)}`);
  if (discountVal > 0) lines.push(`Diskon   : -${formatCurrency(discountVal)}`);
  if (taxVal > 0) lines.push(`Pajak    : ${formatCurrency(taxVal)}`);

  lines.push(line("-"));
  lines.push(`TOTAL    : ${formatCurrency(totalVal)}`);
  lines.push(`Bayar    : ${formatCurrency(paidVal)}`);
  if (changeVal > 0) lines.push(`Kembali  : ${formatCurrency(changeVal)}`);

  lines.push(`Metode   : ${paymentMethod}`);
  lines.push("");

  lines.push(ALIGN_CENTER + "Terima kasih");
  lines.push("atas kunjungan Anda");
  lines.push("");

  /* ================= OUTPUT ================= */
  return (
    INIT_PRINTER +
    NORMAL_FONT +
    FEED_TOP + // <<< PADDING ATAS
    ALIGN_LEFT +
    lines.join("\n") +
    "\n\n\n"
  );
}

/**
 * Cetak struk di desktop (Tauri): jika ada printer default di pengaturan, cetak langsung ke printer; jika tidak, buka dialog print.
 * @param {Object} sale - data penjualan
 * @param {Object} [optsOverride] - opsional: pengaturan printer (paperWidth, fontSize, showStoreHeader, defaultPrinterName) untuk test print
 */
async function printReceiptInDesktop(sale, optsOverride) {
  let store = {};
  try {
    const res = await api.get("/store");
    if (res.data?.name) store = res.data;
  } catch (_) {}

  const printerOpts = optsOverride
    ? normalizePrinterSettings(optsOverride)
    : await getPrinterSettings();

  // Di Tauri selalu pakai command Rust (hindari window.print() di WebView macOS yang menyebabkan "page has no displayID" dan cetak gagal)
  const text = buildReceiptText(sale, store, printerOpts);
  try {
    const { invoke } = await import("@tauri-apps/api/core");
    await invoke("print_receipt_to_printer", {
      printerName: printerOpts.defaultPrinterName || "",
      content: text,
    });
    return {
      success: true,
      method: printerOpts.defaultPrinterName
        ? "desktop-printer"
        : "desktop-default",
    };
  } catch (e) {
    throw new Error(
      e?.message ||
        "Gagal cetak ke printer. Pastikan printer default sistem terpasang atau pilih printer di Pengaturan > Printer.",
    );
  }
}

/**
 * Cetak struk:
 * - Di desktop (Tauri): cetak dari frontend (jendela print / printer sistem)
 * - Di browser: tetap lewat Laravel (printReceiptViaBackend)
 */
export async function printReceipt(sale) {
  if (isTauri()) {
    return printReceiptInDesktop(sale);
  }
  return printReceiptViaBackend(sale);
}

/** Data struk contoh untuk test print */
function getSampleSaleForTest() {
  return {
    id: null,
    invoice_number:
      "TEST-" + new Date().toISOString().slice(0, 10).replace(/-/g, ""),
    sale_date: new Date().toISOString(),
    created_at: new Date().toISOString(),
    user: { name: "Kasir Demo" },
    details: [
      {
        product: { name: "Produk Contoh 1" },
        quantity: 2,
        unit: { name: "pcs" },
        price: 15000,
        subtotal: 30000,
      },
      {
        product: { name: "Produk Contoh 2" },
        quantity: 1,
        unit: { name: "pcs" },
        price: 25000,
        subtotal: 25000,
      },
    ],
    subtotal: 55000,
    discount: 5000,
    tax: 0,
    total: 50000,
    paid: 100000,
    change: 50000,
    payment_method: "cash",
    customer_name: null,
    customer_phone: null,
  };
}

/**
 * Cetak struk uji (test print) dengan data contoh.
 * Di Tauri: cetak ke printer default atau dialog print.
 * Di browser: buka dialog print dengan struk contoh (tanpa backend).
 * @param {Object} [settingsOverride] - opsional: pengaturan form saat ini (paperWidth, fontSize, showStoreHeader, defaultPrinterName) agar test print pakai setting yang belum disimpan
 */
export async function printTestReceipt(settingsOverride) {
  const sampleSale = getSampleSaleForTest();
  if (isTauri()) {
    return printReceiptInDesktop(sampleSale, settingsOverride);
  }
  let store = {};
  try {
    const res = await api.get("/store");
    if (res.data?.name) store = res.data;
  } catch (_) {}
  const printerOpts = settingsOverride
    ? normalizePrinterSettings(settingsOverride)
    : await getPrinterSettings();
  const widthPx = printerOpts.paperWidth === 58 ? 220 : 280;
  const html = buildReceiptHtml(sampleSale, store, printerOpts);
  const containerId = "receipt-test-" + Date.now();
  const styleId = "style-test-" + Date.now();
  const style = document.createElement("style");
  style.id = styleId;
  style.textContent = `
    @media print {
      body * { visibility: hidden !important; }
      #${containerId}, #${containerId} * { visibility: visible !important; }
      #${containerId} { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; background: white !important; z-index: 99999 !important; }
    }
  `;
  document.head.appendChild(style);
  const container = document.createElement("div");
  container.id = containerId;
  container.innerHTML = html;
  container.style.cssText =
    "position: fixed; left: -9999px; top: 0; width: " +
    widthPx +
    "px; background: white; z-index: 99998;";
  document.body.appendChild(container);
  return new Promise((resolve) => {
    const cleanup = () => {
      try {
        document.body.removeChild(container);
      } catch (_) {}
      try {
        document.head.removeChild(style);
      } catch (_) {}
    };
    const doPrint = () => {
      const onAfterPrint = () => {
        window.removeEventListener("afterprint", onAfterPrint);
        cleanup();
        resolve({ success: true, method: "test-print" });
      };
      window.addEventListener("afterprint", onAfterPrint);
      window.print();
    };
    if (document.readyState === "complete") setTimeout(doPrint, 150);
    else window.addEventListener("load", () => setTimeout(doPrint, 150));
  });
}
