# Konversi Variabel Satuan (Multi-Satuan Tidak Tetap)

## Masalah

POS saat ini dirancang untuk **konversi tetap** antar satuan, cocok untuk:
- Bahan bangunan: 1 Roll = 100 Meter (selalu tetap)
- Bahan plastik: 1 Dus = 24 Pcs (selalu tetap)

Untuk **grosir / ritel yang jual barang dengan konversi tidak pasti**, contoh **telur**:
- Dijual per **butir**, per **kg**, atau per **1/4 kg**
- **1 kg telur** bisa berisi **20 butir** atau **19 butir** — tidak tetap
- Stok biasanya dihitung **per butir** (base unit = Butir)

Jika kita pakai `conversion_factor` tetap (mis. 1 Kg = 20 Butir), maka:
- Saat jual 1 kg padahal isi 19 butir → stok berkurang 20, real 19 → **stok tidak akurat**
- Saat jual 1 kg isi 20 butir → oke

Jadi perlu cara agar **jumlah yang dipakai untuk stok** bisa diisi/disesuaikan per transaksi (per line item).

---

## Konsep Solusi: “Konversi Variabel” per Satuan

### Prinsip

1. **Base unit** tetap satu (mis. **Butir** untuk telur) — stok selalu dalam base unit.
2. Produk bisa punya beberapa satuan jual (Butir, Kg, 1/4 Kg, dll.).
3. Sebagian satuan punya **konversi tetap** (mis. 1 Lusin = 12 Butir), sebagian **konversi variabel** (mis. Kg → butir tergantung timbangan/isi).
4. Untuk satuan **variabel**:
   - `conversion_factor` dipakai hanya sebagai **nilai default/estimasi** (mis. 1 Kg ≈ 20 Butir).
   - Saat transaksi, kasir bisa **mengisi atau mengubah “jumlah aktual dalam base unit”** (berapa butir yang benar-benar keluar).
   - Sistem pakai **jumlah aktual** itu untuk pengurangan stok dan laporan; yang tercetak di struk tetap “1 Kg” (satuan jual) + bisa tampilkan “19 Butir” jika diinginkan.

### Contoh alur (telur)

| Base unit | Stok   | Satuan jual | Konversi     | Saat jual 1 Kg |
|-----------|--------|-------------|-------------|----------------|
| Butir     | 500    | Butir       | 1:1         | Input 1 Kg → sistem hitung 20 butir (default), kasir bisa ubah jadi 19 → stok -19 |
| Butir     | 500    | Kg          | Variabel    | Input 1 Kg, “Jumlah aktual (butir): 19” → stok -19, struk: 1 Kg |
| Butir     | 500    | 1/4 Kg      | Variabel    | Input 2 (1/4 Kg), “Jumlah aktual (butir): 10” → stok -10 |

---

## Opsi Implementasi

### Opsi 1: Kolom “Jumlah aktual base” di transaksi (disarankan)

- **Product unit:** tambah flag **`is_variable_conversion`** (boolean).
  - Jika `true`: satuan ini pakai konversi variabel; `conversion_factor` hanya default.
  - Jika `false`: perilaku seperti sekarang (selalu `quantity * conversion_factor`).
- **Sale detail (dan kalau perlu purchase detail):** tambah kolom **`quantity_in_base_unit`** (nullable, decimal).
  - Jika **diisi**: pakai nilai ini untuk pengurangan stok dan perhitungan laporan.
  - Jika **null**: pakai `quantity * conversion_factor` seperti saat ini (backward compatible).
- **API penjualan:** request boleh mengirim `details[].quantity_in_base_unit` (opsional).
  - Untuk satuan dengan `is_variable_conversion = true`, validasi bisa:
    - **Wajib** isi `quantity_in_base_unit`, atau
    - **Opsional**: tidak isi → pakai `quantity * conversion_factor` (default).
- **Frontend POS:** untuk item yang satuan jualnya punya `is_variable_conversion`:
  - Tampilkan field **“Jumlah aktual (base unit)”** (mis. “Jumlah aktual (Butir)”).
  - Pre-fill dari `quantity * conversion_factor`, kasir bisa edit.
  - Kirim ke API sebagai `quantity_in_base_unit`.

**Kelebihan:** Satu model data, mendukung tetap dan variabel, laporan dan stok konsisten.

---

### Opsi 2: Base unit ganda (weight + count) — lebih rumit

- Beberapa sistem punya “base unit berat” dan “base unit jumlah” (mis. Kg dan Butir).
- Stok bisa di-track per berat dan per jumlah; konversi hanya untuk display.
- Ini membutuhkan skema stok dan logic penjualan/pembelian yang lebih kompleks; tidak disarankan kecuali kebutuhan sangat khusus.

---

### Opsi 3: Tidak pakai konversi untuk satuan variabel

- Untuk “Kg” telur, **tidak** definisikan konversi ke Butir di master.
- Setiap kali jual per Kg, kasir input **dua hal**: “1 Kg” (untuk struk) dan “19 Butir” (untuk stok).
- Secara konsep sama dengan Opsi 1; implementasi teknisnya ya dengan kolom `quantity_in_base_unit` + flag variabel, sehingga Opsi 1 lebih rapi (satu tempat konfigurasi).

---

## Rekomendasi

**Gunakan Opsi 1:**  
- Tambah **`is_variable_conversion`** di `product_units`.  
- Tambah **`quantity_in_base_unit`** di `sale_details` (dan kalau pembelian juga perlu, di `purchase_details`).  
- Backend: jika `quantity_in_base_unit` ada, pakai untuk stok; jika tidak, pakai `quantity * conversion_factor`.  
- Frontend: untuk satuan variabel, tampilkan input “Jumlah aktual (base unit)” dan kirim sebagai `quantity_in_base_unit`.

Dengan ini:
- Toko bahan bangunan/plastik tetap pakai konversi tetap seperti sekarang.
- Grosir/ritel untuk barang seperti telur (jual per butir / per kg / per 1/4) bisa akurat stok dan tetap fleksibel per transaksi.

---

## Ringkasan perubahan (jika implementasi Opsi 1)

| Area        | Perubahan |
|------------|-----------|
| **DB**     | `product_units.is_variable_conversion` (boolean, default false); `sale_details.quantity_in_base_unit` (nullable decimal). Opsional: `purchase_details.quantity_in_base_unit` jika pembelian juga pakai konversi variabel. |
| **Backend** | Saat create/update sale (dan return): pakai `quantity_in_base_unit` jika ada, else `quantity * conversion_factor`. Validasi stok dan FIFO pakai `quantity_in_base_unit` bila ada. |
| **API**    | Request body `details[].quantity_in_base_unit` (optional). Response bisa tampilkan `quantity_in_base_unit` di detail penjualan. |
| **Frontend** | Form produk: centang “Konversi variabel” per satuan. POS: jika satuan punya `is_variable_conversion`, tampilkan input “Jumlah aktual (base unit)” dan kirim di payload. |

Jika Anda setuju dengan Opsi 1, langkah berikutnya bisa dilakukan bertahap: migration + model → API sale (dan return) → frontend form produk → frontend POS.
