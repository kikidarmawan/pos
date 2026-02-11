# Catatan: Struk & Printer

## `buildReceiptText` (frontend/src/utils/printReceipt.js)

Fungsi **buildReceiptText** sudah disesuaikan/diperbaiki dengan hal berikut (jangan di-overwrite tanpa perlu):

- **ESC/POS**: Menggunakan kode escape ESC/POS untuk printer thermal:
  - `INIT_PRINTER` (`\x1B@`), `NORMAL_FONT`, `ALIGN_LEFT`, `ALIGN_CENTER`, `FEED_TOP`
- **lineWidth**: Panjang garis pemisah dari `options.lineWidth` (default 32 karakter).
- **Struktur output**: Header toko (center) → STRUK PEMBAYARAN → Invoice/Tanggal/Kasir → ITEM → Subtotal/Total/Bayar/Kembali → Footer "Terima kasih / atas kunjungan Anda".
- **Output**: String diawali `INIT_PRINTER + NORMAL_FONT + FEED_TOP + ALIGN_LEFT + lines.join("\n")` agar printer thermal menerima format yang benar.

Digunakan untuk cetak struk dari POS dan Cetak uji (Tauri) saat mengirim ke printer via command Rust `print_receipt_to_printer`.
