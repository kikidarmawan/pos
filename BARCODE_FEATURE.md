# Fitur Cetak Barcode / Label Produk

## ✅ Fitur yang Sudah Diimplementasi

Sistem POS sekarang sudah dilengkapi dengan fitur cetak barcode dan label produk yang lengkap dan fleksibel.

---

## 🎯 Fitur Utama

### 1. **Multiple Label Types**
- ✅ **Barcode Only** - Hanya barcode saja (untuk sticker barcode)
- ✅ **Label Kecil** (5x3 cm) - Untuk produk kecil
- ✅ **Label Sedang** (7x5 cm) - Standar label produk
- ✅ **Label Besar** (10x7 cm) - Untuk produk besar atau display

### 2. **Barcode Formats**
- ✅ **CODE128** - Format umum, support alphanumeric
- ✅ **EAN13** - Format retail internasional (13 digit)
- ✅ **CODE39** - Format industri

### 3. **Customizable Options**
- ✅ **Jumlah Copy** - Cetak 1-100 label sekaligus
- ✅ **Tampilkan/Sembunyikan:**
  - Nama Produk
  - Kode Produk
  - Harga Jual

### 4. **Smart Pricing**
- ✅ Otomatis pakai harga satuan default
- ✅ Fallback ke base price jika tidak ada satuan default
- ✅ Format currency Indonesia (Rp)

---

## 📁 File yang Dibuat

### 1. **BarcodePrintModal.vue**
```
frontend/src/components/BarcodePrintModal.vue
```

**Fitur:**
- Modal dialog untuk setting print
- Live preview sebelum print
- Grid layout responsive
- Print-friendly CSS

**Dependencies:**
- `jsbarcode` - Generate barcode SVG
- `vue-barcode` - Vue wrapper for JsBarcode

### 2. **Updated Products Index**
```
frontend/src/pages/products/Index.vue
```

**Perubahan:**
- ✅ Tambah tombol print (icon printer) hijau
- ✅ Import BarcodePrintModal component
- ✅ State management untuk modal
- ✅ Tooltips untuk setiap action button

---

## 🚀 Cara Menggunakan

### Dari Halaman Produk:

1. **Buka Menu Produk**
   - Navigate ke halaman Produk

2. **Klik Tombol Print (Icon Printer Hijau)**
   - Setiap baris produk ada icon printer warna hijau
   - Klik untuk membuka print dialog

3. **Atur Setting Print:**
   ```
   ┌─────────────────────────────────────┐
   │ Tipe Label:  [Label Sedang ▼]      │
   │ Jumlah Copy: [1 ────]              │
   │ Format:      [CODE128 ▼]           │
   ├─────────────────────────────────────┤
   │ ☑ Tampilkan Harga                  │
   │ ☑ Tampilkan Nama Produk            │
   │ ☑ Tampilkan Kode Produk            │
   └─────────────────────────────────────┘
   ```

4. **Preview Label**
   - Lihat preview real-time
   - Barcode di-generate otomatis
   - Layout sesuai pilihan

5. **Cetak**
   - Klik tombol "Cetak"
   - Print dialog browser akan muncul
   - Pilih printer & setting
   - Cetak!

---

## 🎨 Contoh Output

### 1. **Barcode Only**
```
┌────────────────────┐
│  ||||||||||||||||  │ ← Barcode
│  1234567890123     │ ← Kode dibawah barcode
└────────────────────┘
```

### 2. **Label Kecil (5x3 cm)**
```
┌──────────────────────┐
│   Kabel Eternal      │ ← Nama Produk
│      KBL001          │ ← Kode Produk
│                      │
│  ||||||||||||||||||  │ ← Barcode
│                      │
│   Rp 5.500/Meter     │ ← Harga
└──────────────────────┘
```

### 3. **Label Sedang (7x5 cm)**
```
┌──────────────────────────┐
│  Kabel Eternal 1.5x3     │ ← Nama (larger)
│       KBL001             │ ← Kode
│                          │
│  |||||||||||||||||||||||  │ ← Barcode (larger)
│                          │
│    Rp 5.500/Meter        │ ← Harga (prominent)
└──────────────────────────┘
```

### 4. **Label Besar (10x7 cm)**
```
┌────────────────────────────────┐
│                                │
│   Kabel Eternal 1.5x3          │ ← Nama (extra large)
│        KBL001                  │ ← Kode
│                                │
│   ||||||||||||||||||||||||||   │ ← Barcode (extra large)
│                                │
│      Rp 5.500/Meter            │ ← Harga (extra large)
│                                │
└────────────────────────────────┘
```

---

## 🔧 Technical Details

### Barcode Generation
```javascript
JsBarcode(element, value, {
  format: 'CODE128',      // Format barcode
  width: 2,               // Ketebalan bar
  height: 60,             // Tinggi barcode
  displayValue: true,     // Tampilkan angka
  fontSize: 12,           // Font size angka
  margin: 5               // Margin
})
```

### Print Styling
```css
@media print {
  @page {
    margin: 10mm;         // Margin kertas
    size: auto;           // Ukuran otomatis
  }
  .label {
    page-break-inside: avoid;  // Label tidak terpotong
    break-inside: avoid;
  }
}
```

### Grid Layout
- **Barcode Only:** 3 columns
- **Label Kecil:** 4 columns (lebih banyak per halaman)
- **Label Sedang:** 3 columns (standar)
- **Label Besar:** 2 columns (lebih sedikit per halaman)

---

## 📝 Use Cases

### 1. **Toko Retail**
```
Scenario: Stok produk baru datang
- Pilih tipe: Label Sedang
- Copy: 50 (untuk 50 unit)
- Show: Nama + Harga + Barcode
- Cetak & tempel di produk
```

### 2. **Warehouse / Gudang**
```
Scenario: Label rak gudang
- Pilih tipe: Label Besar
- Copy: 10 (untuk 10 lokasi rak)
- Show: Kode + Nama + Barcode
- Cetak & tempel di rak
```

### 3. **Price Tag Update**
```
Scenario: Update harga produk
- Pilih tipe: Barcode Only
- Copy: 100
- Show: Hanya barcode
- Cetak label barcode baru
```

### 4. **Product Display**
```
Scenario: Label display toko
- Pilih tipe: Label Besar
- Copy: 1
- Show: Semua info
- Cetak & pajang di display
```

---

## 🎯 Keunggulan

### 1. **Fleksibel**
- Multiple format barcode
- Multiple ukuran label
- Customizable konten

### 2. **User Friendly**
- Live preview
- Easy to use interface
- Tooltips & guidance

### 3. **Print Ready**
- CSS optimized untuk print
- No page break di tengah label
- Responsive grid layout

### 4. **Smart**
- Auto-detect harga default
- Fallback untuk missing data
- Error handling untuk invalid barcode

### 5. **Efficient**
- Batch printing (1-100 copy)
- Fast barcode generation
- Minimal waste

---

## 🧪 Testing

### Test 1: Print Single Label
```
1. Buka halaman Produk
2. Klik icon printer di produk pertama
3. Setting:
   - Tipe: Label Sedang
   - Copy: 1
   - Format: CODE128
4. Cek preview
5. Klik Cetak
6. ✅ Label tercetak dengan benar
```

### Test 2: Batch Print
```
1. Pilih produk
2. Setting Copy: 20
3. Klik Cetak
4. ✅ 20 label tercetak dalam grid 3x7
```

### Test 3: Different Formats
```
1. Test CODE128 ✅
2. Test EAN13 (pastikan barcode valid 13 digit) ✅
3. Test CODE39 ✅
```

### Test 4: Custom Options
```
1. Test tanpa nama ✅
2. Test tanpa harga ✅
3. Test tanpa kode ✅
4. Test barcode only ✅
```

---

## 💡 Tips Penggunaan

### Printer Setting Recommended:
- **Paper:** A4 (210x297mm)
- **Orientation:** Portrait
- **Margins:** Default atau Minimum
- **Scale:** 100% (jangan fit to page)
- **Background graphics:** ON (untuk border)

### Untuk Label Sticker:
1. Gunakan **Label Kecil** atau **Label Sedang**
2. Print ke kertas sticker label
3. Gunakan **barcode-only** untuk efficiency
4. Batch print untuk menghemat waktu

### Untuk Price Tag:
1. Gunakan **Label Sedang** atau **Label Besar**
2. Enable semua info (nama, kode, harga)
3. Print ke karton tebal
4. Laminating untuk durability

### Troubleshooting:
- **Barcode tidak ter-generate:** Pastikan produk punya barcode/code
- **Format error:** Switch ke CODE128 (paling universal)
- **Print terpotong:** Adjust printer margins
- **Terlalu kecil/besar:** Ganti tipe label

---

## 🔮 Future Enhancements (Optional)

### Possible Additions:
- [ ] QR Code support
- [ ] Batch print multiple products
- [ ] Save/load print templates
- [ ] Company logo on label
- [ ] Custom label designer
- [ ] Export to PDF
- [ ] Label templates library
- [ ] Thermal printer support
- [ ] Auto-print on product create

---

## 📊 Summary

| Fitur | Status | Keterangan |
|-------|--------|------------|
| Barcode Generation | ✅ | CODE128, EAN13, CODE39 |
| Multiple Label Sizes | ✅ | 4 ukuran berbeda |
| Batch Printing | ✅ | 1-100 copies |
| Live Preview | ✅ | Real-time preview |
| Custom Options | ✅ | Show/hide nama, kode, harga |
| Print-Friendly CSS | ✅ | Optimized untuk print |
| Smart Pricing | ✅ | Auto-detect harga default |
| Responsive Layout | ✅ | Grid auto-adjust |
| Error Handling | ✅ | Fallback & validation |
| Tooltips | ✅ | User guidance |

**Total: 10/10 Fitur Completed** 🎉

---

## 🚀 Ready to Use!

Fitur cetak barcode/label sudah siap digunakan untuk:
- ✅ Retail stores
- ✅ Warehouse management
- ✅ Inventory tracking
- ✅ Price labeling
- ✅ Product display

**Happy Printing!** 🖨️
