<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Role & Permission</h1>

    <div class="card">
      <p class="text-gray-600 mb-4">
        Halaman untuk mengelola role & permission sistem. Backend sudah support full CRUD.
      </p>
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-800 mb-2">
          <strong>Role Default:</strong>
        </p>
        <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
          <li><strong>Super Admin</strong> - Akses penuh semua fitur</li>
          <li><strong>Admin</strong> - Manajemen master data & laporan</li>
          <li><strong>Kasir</strong> - Transaksi penjualan & view produk</li>
          <li><strong>Gudang</strong> - Pembelian & manajemen stok</li>
        </ul>
      </div>
    </div>
  </div>
</template>
