<template>
  <div ref="posContainerRef"
    class="pos-page min-h-[calc(100dvh-6rem)] lg:h-[calc(100vh-8.5rem)] pb-20 flex flex-col lg:overflow-hidden">
    <div class="pos-page-grid grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6 min-h-0 flex-1">
      <!-- Left: Customer + Cart Table -->
      <div class="lg:col-span-2 space-y-3 order-1 flex flex-col min-h-0">
        <!-- Toolbar: Cari Produk + Warehouse + Actions -->
        <div class="card p-3">
          <div class="flex gap-2 items-center flex-wrap">
            <button type="button" @click="openProductModal" class="btn btn-primary btn-sm shrink-0">
              <MagnifyingGlassIcon class="w-4 h-4 mr-1.5" />
              Cari Produk
            </button>
            <div class="relative w-80 shrink-0">
              <input ref="barcodeInputRef" v-model="barcodeInput" @keyup.enter="handleBarcodeScan" type="text"
                class="input input-sm w-full pl-8" placeholder="Scan Barcode atau Kode satuan..."
                :disabled="isScanning" />
              <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                <div v-if="isScanning"
                  class="w-4 h-4 border-2 border-primary-600 border-t-transparent rounded-full animate-spin"></div>
                <QrCodeIcon v-else class="w-4 h-4 text-gray-400" />
              </div>
            </div>
            <select v-model="selectedWarehouse" required class="input input-sm w-auto min-w-[140px]"
              @change="loadProducts">
              <option value="">Pilih Gudang</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                {{ wh.name }}
              </option>
            </select>
            <div class="flex-1"></div>
            <button type="button" @click="toggleFullscreen"
              class="p-2 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 hover:text-primary-600 transition-colors shrink-0"
              :title="isFullscreen ? 'Keluar Fullscreen (ESC)' : 'Fullscreen'">
              <ArrowsPointingOutIcon v-if="!isFullscreen" class="w-5 h-5" />
              <ArrowsPointingInIcon v-else class="w-5 h-5" />
            </button>
            <!-- <button type="button" @click="connectPrinter" disabled
              class="p-2 rounded-lg border border-gray-200 text-gray-400 transition-colors shrink-0 flex items-center gap-1.5 opacity-50 cursor-not-allowed"
              title="Printer (segera hadir)">
              <PrinterIcon class="w-5 h-5" :class="printerReady ? 'text-green-600' : ''" />
              <span v-if="printerReady" class="text-xs text-green-600 hidden sm:inline">Siap</span>
              <span v-else class="text-xs hidden sm:inline">{{ printerConnecting ? '...' : 'Printer' }}</span>
            </button> -->
          </div>
        </div>

        <!-- Cart Table -->
        <div class="card p-4 flex-1 flex flex-col min-h-0">
          <div class="flex items-center justify-between mb-3 shrink-0">
            <h2 class="text-lg font-bold">Keranjang</h2>
            <span v-if="cartStore.items.length" class="text-sm text-gray-500">{{ cartStore.items.length }} item</span>
          </div>
          <div v-if="!cartStore.items.length"
            class="text-center text-gray-400 flex-1 flex flex-col items-center justify-center min-h-[200px]">
            <ShoppingCartIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
            <p>Keranjang masih kosong</p>
            <p class="text-sm mt-1">Klik <strong>"Cari Produk"</strong> untuk menambahkan item</p>
          </div>
          <div v-else class="overflow-y-auto overflow-x-auto -mx-4 flex-1 min-h-0">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 text-xs text-gray-600 uppercase sticky top-0 z-10 shadow-sm">
                <tr>
                  <th class="px-2 py-1.5 text-left w-6">#</th>
                  <th class="px-2 py-1.5 text-left">Produk</th>
                  <th class="px-2 py-1.5 text-left hidden sm:table-cell">Satuan</th>
                  <th class="px-2 py-1.5 text-center w-24">Qty</th>
                  <th class="px-2 py-1.5 text-right hidden md:table-cell">Harga</th>
                  <th class="px-2 py-1.5 text-center hidden lg:table-cell w-20">Diskon</th>
                  <th class="px-2 py-1.5 text-right">Subtotal</th>
                  <th class="px-2 py-1.5 w-6"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(item, index) in cartStore.items" :key="index" class="hover:bg-gray-50 transition-colors">
                  <td class="px-2 py-2 text-gray-400 text-xs">{{ index + 1 }}</td>
                  <td class="px-2 py-2">
                    <div class="min-w-0">
                      <p class="font-medium text-xs whitespace-normal line-clamp-2">{{ item.product.name }}</p>
                      <button @click="openUnitChange(index, item)" type="button"
                        class="text-[10px] text-primary-600 sm:hidden mt-0.5 hover:underline inline-flex items-center gap-0.5">
                        {{ item.unit.name }} <span>&#9660;</span>
                      </button>
                    </div>
                  </td>
                  <td class="px-2 py-2 text-gray-600 hidden sm:table-cell text-xs">
                    <button @click="openUnitChange(index, item)" type="button"
                      class="hover:text-primary-600 hover:bg-gray-100 px-1.5 py-0.5 -ml-1.5 rounded transition-colors inline-flex items-center gap-1">
                      {{ item.unit.name }} <span class="text-[10px]">&#9660;</span>
                    </button>
                  </td>
                  <td class="px-2 py-2">
                    <div class="flex items-center justify-center gap-0.5">
                      <button @click="cartStore.updateQuantity(index, item.quantity - 1)"
                        class="px-1 py-0.5 bg-gray-200 rounded hover:bg-gray-300 text-xs font-medium">-</button>
                      <input :value="item.quantity"
                        @change="cartStore.updateQuantity(index, parseFloat($event.target.value))" type="number"
                        step="0.01" class="w-10 text-center border rounded px-1 py-0.5 text-xs" />
                      <button @click="cartStore.updateQuantity(index, item.quantity + 1)"
                        class="px-1 py-0.5 bg-gray-200 rounded hover:bg-gray-300 text-xs font-medium">+</button>
                    </div>
                  </td>
                  <td class="px-2 py-2 text-right text-gray-600 hidden md:table-cell text-xs">{{
                    formatCurrency(item.price) }}
                  </td>
                  <td class="px-2 py-2 hidden lg:table-cell">
                    <input :value="item.discount ?? 0"
                      @input="cartStore.updateItemDiscount(index, ($event.target).value)" type="number" step="100"
                      min="0" class="w-16 text-xs text-center border rounded px-1 py-0.5" placeholder="0" />
                  </td>
                  <td class="px-2 py-2 text-right">
                    <template v-if="(item.discount || 0) > 0">
                      <p class="text-[10px] text-gray-400 line-through">{{ formatCurrency(item.price * item.quantity) }}
                      </p>
                      <p class="font-bold text-green-600 text-xs">{{ formatCurrency(item.subtotal) }}</p>
                    </template>
                    <template v-else>
                      <p class="font-bold text-xs">{{ formatCurrency(item.subtotal) }}</p>
                    </template>
                  </td>
                  <td class="px-2 py-2">
                    <button @click="cartStore.removeItem(index)"
                      class="text-red-400 hover:text-red-600 transition-colors">
                      <XMarkIcon class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right: Pelanggan + Ringkasan -->
      <div class="lg:col-span-1 order-2 lg:sticky space-y-4" :class="isFullscreen ? 'lg:top-4' : 'lg:top-20'">
        <!-- Pelanggan -->
        <div class="card">
          <h2 class="text-lg font-bold mb-3">Pelanggan</h2>
          <div class="space-y-2">
            <div>
              <label class="label text-xs py-0.5">Pilih Pelanggan</label>
              <div class="flex gap-1">
                <VSelect v-model="selectedCustomer" :options="customers" :reduce="(c) => c" label="name"
                  placeholder="Pilih pelanggan..." :filterable="true" :clearable="true"
                  class="flex-1 vue-select-compact" />
                <button type="button" @click="showCustomerModal = true" class="btn btn-primary btn-sm shrink-0 p-2"
                  title="Tambah Pelanggan Baru">
                  <PlusIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
            <div>
              <label class="label text-xs py-0.5">Nama</label>
              <input v-model="cartStore.customer.name" type="text" class="input input-sm py-1.5"
                :placeholder="selectedCustomer ? '' : 'Walk-in'" />
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="label text-xs py-0.5">No. HP</label>
                <input v-model="cartStore.customer.phone" type="text" class="input input-sm py-1.5"
                  placeholder="08xxx" />
              </div>
              <div>
                <label class="label text-xs py-0.5">Alamat</label>
                <input v-model="cartStore.customer.address" type="text" class="input input-sm py-1.5"
                  placeholder="Opsional" />
              </div>
            </div>
          </div>
        </div>

        <!-- Ringkasan -->
        <div class="card">
          <h2 class="text-lg font-bold mb-3">Ringkasan</h2>
          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Subtotal:</span>
              <span class="font-medium">{{ formatCurrency(cartStore.subtotal) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Diskon:</span>
              <input v-model="cartStore.discount" type="number" step="1000"
                class="w-32 text-right border rounded px-2 py-1.5 text-sm" />
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Pajak (%):</span>
              <input v-model="cartStore.tax" type="number" step="1"
                class="w-32 text-right border rounded px-2 py-1.5 text-sm" />
            </div>
            <div class="border-t pt-3 mt-3">
              <div class="flex justify-between text-lg font-bold">
                <span>Total:</span>
                <span class="text-primary-600">{{ formatCurrency(cartStore.total) }}</span>
              </div>
            </div>
            <p class="text-xs text-gray-400 pt-1">{{ cartStore.items.length }} item di keranjang</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons - fixed bottom, horizontal, full width (not in sidebar; left-0 when fullscreen) -->
    <div class="fixed bottom-0 left-0 right-0 z-20 bg-white border-t shadow-lg py-3"
      :class="isFullscreen ? 'px-6' : 'lg:left-64 px-4'">
      <div class="flex flex-wrap gap-2 lg:gap-4 items-center justify-between w-full"
        :class="isFullscreen ? '' : 'max-w-7xl mx-auto'">

        <!-- Cashier Info -->
        <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600">
          <UserIcon class="w-4 h-4 lg:w-5 lg:h-5 text-gray-400" />
          <span>Kasir: <span class="font-medium text-gray-900">{{ authStore.user?.name || 'Kasir' }}</span></span>
        </div>

        <div class="flex flex-nowrap gap-1.5 lg:gap-2 justify-end">
          <button @click="openPaymentModal" :disabled="!cartStore.items.length || !selectedWarehouse"
            class="btn btn-success btn-sm lg:btn-sm-reset text-xs lg:text-sm px-2.5 py-1.5 lg:px-4 lg:py-2">
            <CurrencyDollarIcon class="w-4 h-4 lg:w-5 lg:h-5 mr-1 lg:mr-2" />
            Bayar
          </button>
          <button @click="holdTransaction" :disabled="!cartStore.items.length || !selectedWarehouse"
            class="btn btn-secondary btn-sm lg:btn-sm-reset text-xs lg:text-sm px-2.5 py-1.5 lg:px-4 lg:py-2">
            <ClockIcon class="w-4 h-4 lg:w-5 lg:h-5 mr-1 lg:mr-2" />
            Tahan
          </button>
          <button @click="openHeldModal"
            class="btn btn-outline btn-sm lg:btn-sm-reset text-xs lg:text-sm px-2.5 py-1.5 lg:px-4 lg:py-2"
            :class="heldCount > 0 ? 'border-amber-500 text-amber-600' : ''">
            <FolderIcon class="w-4 h-4 lg:w-5 lg:h-5 mr-1 lg:mr-2" />
            <span class="hidden sm:inline">Tertahan</span><span class="sm:hidden">{{ heldCount }}</span>
            <span class="hidden sm:inline">({{ heldCount }})</span>
          </button>
          <button @click="cartStore.clear()" :disabled="!cartStore.items.length"
            class="btn btn-danger btn-sm lg:btn-sm-reset text-xs lg:text-sm px-2.5 py-1.5 lg:px-4 lg:py-2">
            <TrashIcon class="w-4 h-4 lg:w-5 lg:h-5 mr-1 lg:mr-2" />
            Hapus
          </button>
        </div>
      </div>
    </div>

    <!-- Product Search Modal -->
    <div v-if="showProductModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="showProductModal = false">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl h-[85vh] overflow-hidden flex flex-col">
        <div class="p-4 border-b flex items-center justify-between shrink-0">
          <h3 class="text-lg font-bold">Cari Produk</h3>
          <button @click="showProductModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>
        <div class="p-4 border-b shrink-0">
          <input ref="productSearchInput" v-model="search" @input="searchProducts" type="text"
            placeholder="Cari produk (nama, kode, barcode)..." class="input" />
        </div>
        <div class="p-4 flex-1 overflow-y-auto">
          <div v-if="loadingProducts" class="grid place-items-center py-16">
            <div class="text-center">
              <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600"></div>
              <p class="mt-2 text-gray-500">Memuat produk...</p>
            </div>
          </div>
          <div v-else-if="!selectedWarehouse" class="py-16 text-center text-gray-500">
            Pilih gudang untuk menampilkan produk
          </div>
          <div v-else-if="products.length === 0" class="py-16 text-center text-gray-500">
            {{ search ? 'Tidak ada produk ditemukan' : 'Belum ada produk' }}
          </div>
          <div v-else class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="w-full text-sm text-left border-collapse">
              <thead class="bg-gray-50 text-gray-600 text-xs uppercase sticky top-0 shadow-sm z-10">
                <tr>
                  <th class="px-4 py-2.5 border-b font-semibold">Kode</th>
                  <th class="px-4 py-2.5 border-b font-semibold">Barcode</th>
                  <th class="px-4 py-2.5 border-b font-semibold">Nama Produk</th>
                  <th class="px-4 py-2.5 border-b font-semibold">Satuan</th>
                  <th class="px-4 py-2.5 border-b font-semibold">Kode Satuan</th>
                  <th class="px-4 py-2.5 border-b font-semibold text-right">Harga</th>
                  <th class="px-4 py-2.5 border-b font-semibold text-center">Stok</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <template v-for="product in products" :key="product.id">
                  <tr v-for="pu in getSortedUnits(product)" :key="product.id + '-' + pu.id"
                    @click="addToCartFromSearch(product, pu)" class="cursor-pointer hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap">{{ product.code }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap">{{ product.barcode || '-' }}</td>
                    <td class="px-4 py-2.5 font-medium text-gray-900">{{ product.name }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap">{{ pu.unit?.name || pu.name }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap">{{ pu.barcode || '-' }}</td>
                    <td class="px-4 py-2.5 text-right font-bold text-primary-600 whitespace-nowrap">{{
                      formatCurrency(pu.selling_price || product.base_price) }}</td>
                    <td class="px-4 py-2.5 text-center text-xs whitespace-nowrap"
                      :class="stockForUnitOfProduct(product, pu) <= 0 ? 'text-red-600 font-medium' : 'text-gray-500'">
                      {{ formatStock(stockForUnitOfProduct(product, pu)) }}
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Unit Selector Modal -->
    <div v-if="showUnitSelector"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-14 h-14 shrink-0 rounded-lg bg-gray-200 overflow-hidden flex items-center justify-center">
            <img v-if="selectedProduct?.image" :src="selectedProduct.image" :alt="selectedProduct?.name"
              class="w-full h-full object-cover" />
            <CubeIcon v-else class="w-7 h-7 text-gray-400" />
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-xl font-bold">{{ selectedProduct?.name }}</h3>
            <p class="text-sm text-gray-500">{{ selectedProduct?.code }}</p>
          </div>
        </div>
        <p class="text-sm text-gray-600 mb-3">Pilih satuan jual:</p>
        <div class="space-y-2">
          <button v-for="pu in (selectedProduct?.product_units || selectedProduct?.productUnits || [])" :key="pu.id"
            @click="addToCart(pu)"
            class="w-full p-4 text-left border-2 rounded-lg hover:border-primary-600 hover:bg-primary-50 transition-colors">
            <div class="flex justify-between items-start gap-2">
              <div class="min-w-0 flex-1">
                <p class="font-medium">{{ pu.unit?.name ?? '' }}</p>
                <p class="text-sm text-gray-500">Konversi: {{ formatIntegerOrDecimal(pu.conversion_factor) }}x</p>
                <p class="text-xs mt-1"
                  :class="(stockForUnit(pu) || 0) < (selectedProduct?.minimum_stock ?? 0) ? 'text-red-600 font-medium' : 'text-gray-500'">
                  Stok: {{ formatStock(stockForUnit(pu)) }} {{ pu.unit?.name ?? '' }}
                </p>
              </div>
              <p class="font-bold text-primary-600 shrink-0">{{ formatCurrency(pu.selling_price) }}</p>
            </div>
          </button>
        </div>
        <button @click="closeUnitSelector" class="w-full mt-4 btn btn-secondary">
          Batal
        </button>
      </div>
    </div>

    <!-- Payment Modal -->
    <PaymentModal v-if="showPaymentModal" :total="cartStore.total" :warehouse-id="selectedWarehouse"
      @close="closePaymentModal" @success="handlePaymentSuccess" />

    <!-- Customer Form Modal -->
    <CustomerFormModal v-if="showCustomerModal" @close="showCustomerModal = false" @saved="handleCustomerSaved" />

    <!-- Printer Alert Modal -->
    <div v-if="showPrinterModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="closePrinterModal">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex items-start gap-3">
          <div v-if="printerModalContent.type === 'success'"
            class="shrink-0 w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
            <PrinterIcon class="w-6 h-6 text-green-600" />
          </div>
          <div v-else-if="printerModalContent.type === 'error'"
            class="shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
            <PrinterIcon class="w-6 h-6 text-red-600" />
          </div>
          <div v-else class="shrink-0 w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
            <PrinterIcon class="w-6 h-6 text-blue-600" />
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-bold text-gray-900 mb-1">{{ printerModalContent.title }}</h3>
            <p class="text-gray-600">{{ printerModalContent.message }}</p>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
          <template v-if="printerModalContent.confirmMode">
            <button type="button" @click="closePrinterModal" class="btn btn-secondary">
              Batal
            </button>
            <button type="button" @click="onPrinterModalConfirm" class="btn btn-primary">
              Lanjutkan
            </button>
          </template>
          <template v-else>
            <button type="button" @click="closePrinterModal" class="btn btn-primary">
              Tutup
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Held Transactions Modal -->
    <div v-if="showHeldModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click.self="showHeldModal = false">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col">
        <div class="p-4 border-b">
          <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-bold">Transaksi Tertahan</h3>
            <button @click="showHeldModal = false" class="text-gray-500 hover:text-gray-700">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>
          <input v-model="heldSearch" type="text" placeholder="Cari nama, no HP, alamat, atau gudang..."
            class="input w-full" />
        </div>
        <div class="p-4 overflow-y-auto flex-1">
          <div v-if="loadingHolds" class="text-center py-8">Memuat...</div>
          <div v-else-if="!filteredHeldTransactions.length" class="text-center py-8 text-gray-500">
            {{ heldSearch ? 'Tidak ada hasil pencarian' : 'Tidak ada transaksi tertahan' }}
          </div>
          <div v-else class="space-y-3">
            <div v-for="hold in filteredHeldTransactions" :key="hold.id"
              class="border rounded-lg p-3 flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
                <p class="font-medium text-sm">{{ hold.warehouse?.name }}</p>
                <p v-if="hold.customer_name" class="text-sm text-gray-700 mt-1">
                  {{ hold.customer_name }}
                </p>
                <p v-if="hold.customer_phone" class="text-xs text-gray-500">{{ hold.customer_phone }}</p>
                <p v-if="hold.customer_address" class="text-xs text-gray-500 truncate" :title="hold.customer_address">
                  {{ hold.customer_address }}
                </p>
                <p class="text-xs text-gray-500 mt-1">{{ hold.items?.length || 0 }} item •
                  {{ formatCurrency(holdTotal(hold)) }}</p>
                <p class="text-xs text-gray-400">{{ formatDateTime(hold.created_at) }}</p>
              </div>
              <div class="flex gap-2 shrink-0">
                <button @click="restoreHold(hold)" class="btn btn-primary btn-sm">
                  Lanjutkan
                </button>
                <button @click="removeHold(hold)" class="btn btn-danger btn-sm">Hapus</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pos-page:fullscreen,
.pos-page:-webkit-full-screen,
.pos-page:-moz-full-screen,
.pos-page:-ms-fullscreen {
  background-color: #f9fafb !important;
  width: 100vw !important;
  height: 100vh !important;
  min-height: 100vh !important;
  overflow: hidden !important;
  padding: 1.5rem 1.5rem !important;
  box-sizing: border-box !important;
  display: flex !important;
  flex-direction: column !important;
}

.pos-page:fullscreen>.pos-page-grid,
.pos-page:-webkit-full-screen>.pos-page-grid,
.pos-page:-moz-full-screen>.pos-page-grid,
.pos-page:-ms-fullscreen>.pos-page-grid {
  flex: 1 1 0 !important;
  min-height: 0 !important;
  overflow: hidden !important;
}
</style>
<style>
/* Non-scoped: fullscreen pseudo-class needs global scope in some browsers */
.pos-page:fullscreen {
  background-color: #f9fafb !important;
  width: 100vw !important;
  height: 100vh !important;
  min-height: 100vh !important;
  overflow: hidden !important;
  padding: 1.5rem 1.5rem !important;
  box-sizing: border-box !important;
  display: flex !important;
  flex-direction: column !important;
}

.pos-page:fullscreen>.pos-page-grid {
  flex: 1 1 0 !important;
  min-height: 0 !important;
  overflow: hidden !important;
}

.pos-page:-webkit-full-screen {
  background-color: #f9fafb !important;
  width: 100vw !important;
  height: 100vh !important;
  min-height: 100vh !important;
  overflow: hidden !important;
  padding: 1.5rem 1.5rem !important;
  box-sizing: border-box !important;
  display: flex !important;
  flex-direction: column !important;
}

.pos-page:-webkit-full-screen>.pos-page-grid {
  flex: 1 1 0 !important;
  min-height: 0 !important;
  overflow: hidden !important;
}

.pos-page:-moz-full-screen {
  background-color: #f9fafb !important;
  width: 100vw !important;
  height: 100vh !important;
  min-height: 100vh !important;
  overflow: hidden !important;
  padding: 1.5rem 1.5rem !important;
  box-sizing: border-box !important;
  display: flex !important;
  flex-direction: column !important;
}

.pos-page:-moz-full-screen>.pos-page-grid {
  flex: 1 1 0 !important;
  min-height: 0 !important;
  overflow: hidden !important;
}

.pos-page:-ms-fullscreen {
  background-color: #f9fafb !important;
  width: 100vw !important;
  height: 100vh !important;
  min-height: 100vh !important;
  overflow: hidden !important;
  padding: 1.5rem 1.5rem !important;
  box-sizing: border-box !important;
  display: flex !important;
  flex-direction: column !important;
}

.pos-page:-ms-fullscreen>.pos-page-grid {
  flex: 1 1 0 !important;
  min-height: 0 !important;
  overflow: hidden !important;
}
</style>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useCartStore } from '@/stores/cart'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatStock, formatDateTime, formatIntegerOrDecimal } from '@/utils/format'
import {
  CubeIcon,
  XMarkIcon,
  CurrencyDollarIcon,
  TrashIcon,
  PlusIcon,
  ClockIcon,
  FolderIcon,
  ArrowsPointingOutIcon,
  ArrowsPointingInIcon,
  PrinterIcon,
  MagnifyingGlassIcon,
  ShoppingCartIcon,
  QrCodeIcon,
  UserIcon
} from '@heroicons/vue/24/outline'
import PaymentModal from './PaymentModal.vue'
import CustomerFormModal from '@/components/CustomerFormModal.vue'
import { useAuthStore } from '@/stores/auth'

const cartStore = useCartStore()
const authStore = useAuthStore()
const toast = useToast()

const search = ref('')
const products = ref([])
const warehouses = ref([])
const selectedWarehouse = ref('')
const selectedProduct = ref(null)
const showUnitSelector = ref(false)
const showPaymentModal = ref(false)
const showCustomerModal = ref(false)
const showHeldModal = ref(false)
const showProductModal = ref(false)
const loadingProducts = ref(false)
const loadingHolds = ref(false)
const customers = ref([])
const selectedCustomer = ref(null)
const heldTransactions = ref([])
const heldSearch = ref('')
const posContainerRef = ref(null)
const productSearchInput = ref(null)
const barcodeInputRef = ref(null)
const barcodeInput = ref('')
const isScanning = ref(false)
const isFullscreen = ref(false)
const showPrinterModal = ref(false)
const printerModalContent = ref({ title: '', message: '', type: 'info' })
const printerReady = ref(false)
const printerConnecting = ref(false)
const changingCartIndex = ref(null)

const checkPrinterReady = () => { }

const openPrinterModal = (title, message, type = 'info', confirmMode = false) => {
  printerModalContent.value = { title, message, type, confirmMode }
  showPrinterModal.value = true
}

const closePrinterModal = () => {
  showPrinterModal.value = false
}

const printerModalOnConfirm = ref(null)

const onPrinterModalConfirm = async () => {
  const fn = printerModalOnConfirm.value
  closePrinterModal()
  if (typeof fn === 'function') {
    printerModalOnConfirm.value = null
    await fn()
  }
}

const connectPrinter = () => {
  const isDesktop = typeof window !== 'undefined' && !!window.__TAURI__
  const message = isDesktop
    ? 'Cetak struk dilakukan dari aplikasi. Setelah transaksi selesai, klik Cetak Struk di modal pembayaran—dialog print sistem akan terbuka dan Anda bisa pilih printer atau Simpan sebagai PDF.'
    : 'Cetak struk dilakukan via backend setelah pembayaran. Klik Cetak Struk di modal pembayaran setelah transaksi selesai. Pastikan printer terhubung ke server.'
  openPrinterModal('Printer', message, 'info')
}

onMounted(checkPrinterReady)

const toggleFullscreen = async () => {
  if (!posContainerRef.value) return
  try {
    if (!document.fullscreenElement) {
      await posContainerRef.value.requestFullscreen?.() ||
        posContainerRef.value.webkitRequestFullscreen?.() ||
        posContainerRef.value.mozRequestFullScreen?.() ||
        posContainerRef.value.msRequestFullscreen?.()
    } else {
      await document.exitFullscreen?.() ||
        document.webkitExitFullscreen?.() ||
        document.mozCancelFullScreen?.() ||
        document.msExitFullscreen?.()
    }
  } catch (e) {
    toast.error('Fullscreen tidak didukung')
  }
}

const onFullscreenChange = () => {
  const active = !!(
    document.fullscreenElement ||
    document.webkitFullscreenElement ||
    document.mozFullScreenElement ||
    document.msFullscreenElement
  )
  isFullscreen.value = active
  if (posContainerRef.value) {
    posContainerRef.value.style.backgroundColor = active ? '#f9fafb' : ''
    posContainerRef.value.style.minHeight = active ? '100vh' : ''
    posContainerRef.value.style.minWidth = active ? '100vw' : ''
    // Move toast container into fullscreen element so toasts are visible
    const toastContainers = document.querySelectorAll('.Vue-Toastification__container')
    toastContainers.forEach((el) => {
      if (active) {
        posContainerRef.value.appendChild(el)
      } else {
        document.body.appendChild(el)
      }
    })
  }
}

const filteredHeldTransactions = computed(() => {
  const q = heldSearch.value.trim().toLowerCase()
  if (!q) return heldTransactions.value
  const qClean = q.replace(/\s/g, '')
  return heldTransactions.value.filter((hold) => {
    const name = (hold.customer_name || '').toLowerCase()
    const phone = (hold.customer_phone || '').replace(/\s/g, '')
    const address = (hold.customer_address || '').toLowerCase()
    const warehouse = (hold.warehouse?.name || '').toLowerCase()
    return name.includes(q) || address.includes(q) || warehouse.includes(q) || phone.includes(qClean)
  })
})

const loadProducts = async () => {
  if (!selectedWarehouse.value) {
    products.value = []
    loadingProducts.value = false
    return
  }

  loadingProducts.value = true
  try {
    const params = {
      is_active: 1,
      per_page: 50,
      warehouse_id: selectedWarehouse.value
    }
    if (search.value.trim().length >= 2) {
      params.search = search.value.trim()
    }
    const response = await api.get('/products', { params })
    products.value = response.data.data || []
  } catch (error) {
    console.error('Error loading products:', error)
    toast.error('Gagal memuat produk')
  } finally {
    loadingProducts.value = false
  }
}

const searchProducts = () => {
  loadProducts()
}

const openProductModal = () => {
  showProductModal.value = true
  nextTick(() => {
    productSearchInput.value?.focus()
  })
}

const loadCustomers = async () => {
  try {
    const res = await api.get('/customers', { params: { per_page: 200, is_active: 1 } })
    customers.value = res.data.data || []
  } catch (e) {
    console.error('Failed to load customers', e)
  }
}

const handleCustomerSaved = (customer) => {
  customers.value = [customer, ...customers.value]
  selectedCustomer.value = customer
  cartStore.setCustomer({
    id: customer.id,
    name: customer.name,
    phone: customer.phone || '',
    address: customer.address || ''
  })
}

const heldCount = computed(() => heldTransactions.value.length)

const holdTotal = (hold) => {
  return (hold.items || []).reduce((sum, i) => sum + (i.subtotal || 0), 0)
}

const loadHeldTransactions = async () => {
  loadingHolds.value = true
  try {
    const res = await api.get('/sale-holds')
    heldTransactions.value = res.data || []
  } catch (e) {
    console.error('Failed to load held transactions', e)
    toast.error('Gagal memuat transaksi tertahan')
  } finally {
    loadingHolds.value = false
  }
}

const holdTransaction = async () => {
  if (!cartStore.items.length || !selectedWarehouse.value) return
  try {
    const items = cartStore.items.map((item) => ({
      product_id: item.product.id,
      unit_id: item.unit.id,
      quantity: item.quantity,
      price: item.price,
      discount: item.discount || 0,
      subtotal: item.subtotal
    }))
    await api.post('/sale-holds', {
      warehouse_id: selectedWarehouse.value,
      customer_id: cartStore.customer.id || null,
      customer_name: cartStore.customer.name || '',
      customer_phone: cartStore.customer.phone || '',
      customer_address: cartStore.customer.address || '',
      discount: cartStore.discount,
      tax: cartStore.tax,
      items
    })
    toast.success('Transaksi berhasil ditahan')
    cartStore.clear()
    selectedCustomer.value = null
    loadHeldTransactions()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menahan transaksi')
  }
}

const restoreHold = async (hold) => {
  try {
    const res = await api.get(`/sale-holds/${hold.id}`)
    const data = res.data
    cartStore.loadFromHold({
      items: data.items,
      customer: data.customer,
      discount: data.hold.discount,
      tax: data.hold.tax
    })
    selectedWarehouse.value = data.hold.warehouse_id
    if (data.customer?.id) {
      const cust = customers.value.find((c) => c.id === data.customer.id)
      selectedCustomer.value = cust || { id: data.customer.id, name: data.customer.name, phone: data.customer.phone, address: data.customer.address }
    } else {
      selectedCustomer.value = null
    }
    await api.delete(`/sale-holds/${hold.id}`)
    loadHeldTransactions()
    showHeldModal.value = false
    loadProducts()
    toast.success('Transaksi berhasil dilanjutkan')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal memuat transaksi')
  }
}

const removeHold = async (hold) => {
  if (!confirm('Hapus transaksi tertahan ini?')) return
  try {
    await api.delete(`/sale-holds/${hold.id}`)
    loadHeldTransactions()
    toast.success('Transaksi tertahan dihapus')
  } catch (e) {
    toast.error('Gagal menghapus')
  }
}

const openHeldModal = () => {
  heldSearch.value = ''
  showHeldModal.value = true
  loadHeldTransactions()
}

const loadWarehouses = async () => {
  try {
    const response = await api.get('/warehouses', {
      params: { is_active: 1, per_page: 100 }
    })
    warehouses.value = response.data.data
    if (warehouses.value.length > 0) {
      selectedWarehouse.value = warehouses.value[0].id
      await loadProducts()
    }
  } catch (error) {
    toast.error('Gagal memuat gudang')
  }
}

const getSortedUnits = (product) => {
  const units = product.product_units || product.productUnits || []
  if (!units || units.length === 0) {
    // Return a dummy unit representing the base unit if no product_units are defined
    return [{
      id: 'base',
      unit: product.base_unit || product.baseUnit,
      name: product.base_unit?.name || product.baseUnit?.name || '-',
      selling_price: product.base_price,
      conversion_factor: 1
    }]
  }

  // Urutkan dari satuan terkecil (conversion_factor terkecil)
  return [...units].sort((a, b) => {
    return (Number(a.conversion_factor) || 1) - (Number(b.conversion_factor) || 1)
  })
}

const addToCartFromSearch = (product, unit) => {
  const success = addToCartDirectly(product, unit)
  if (success) {
    showProductModal.value = false
  }
}

const openUnitSelector = (product) => {
  const units = product.product_units || product.productUnits || []
  if (units.length === 1) {
    const success = addToCartDirectly(product, units[0])
    if (success) {
      showProductModal.value = false
    }
    return
  }

  changingCartIndex.value = null
  selectedProduct.value = product
  showUnitSelector.value = true
}

const openUnitChange = (index, item) => {
  changingCartIndex.value = index
  selectedProduct.value = item.product
  showUnitSelector.value = true
}

const closeUnitSelector = () => {
  showUnitSelector.value = false
  selectedProduct.value = null
  changingCartIndex.value = null
}

/** Stok dalam satuan unit ini (total_stock produk dalam base unit / conversion_factor) */
const stockForUnitOfProduct = (product, pu) => {
  const total = product?.total_stock ?? 0
  const factor = Number(pu.conversion_factor) || 1
  return factor > 0 ? total / factor : 0
}

const stockForUnit = (pu) => stockForUnitOfProduct(selectedProduct.value, pu)

const addToCartDirectly = (product, productUnit) => {
  const stockAvailable = stockForUnitOfProduct(product, productUnit)
  const existingItem = cartStore.items.find(
    (item) => item.product.id === product.id && item.unit.id === productUnit.unit?.id
  )
  const qtyInCart = existingItem ? existingItem.quantity : 0
  const qtyAfterAdd = qtyInCart + 1

  if (stockAvailable <= 0 || qtyAfterAdd > stockAvailable) {
    toast.error(`Stok produk "${product.name}" tidak tersedia`)
    return false
  }
  cartStore.addItem(product, productUnit.unit, productUnit)
  return true
}

const addToCart = (productUnit) => {
  if (changingCartIndex.value !== null) {
    const currentQty = cartStore.items[changingCartIndex.value].quantity
    const stockAvailable = stockForUnitOfProduct(selectedProduct.value, productUnit)
    if (currentQty > stockAvailable) {
      toast.error(`Stok produk tidak mencukupi untuk satuan ini`)
      return
    }
    cartStore.changeItemUnit(changingCartIndex.value, productUnit.unit, productUnit)
    closeUnitSelector()
    return
  }

  const success = addToCartDirectly(selectedProduct.value, productUnit)
  if (success) {
    closeUnitSelector()
    showProductModal.value = false
  }
}

const handleBarcodeScan = async () => {
  if (!barcodeInput.value.trim() || !selectedWarehouse.value) return
  isScanning.value = true
  try {
    const params = {
      is_active: 1,
      per_page: 2,
      warehouse_id: selectedWarehouse.value,
      barcode: barcodeInput.value.trim()
    }
    const response = await api.get('/products', { params })
    const matchedProducts = response.data.data || []

    // Karena kita sudah pakai parameter `barcode`, hasil dari backend sudah pasti exact match
    const exactMatch = matchedProducts[0]

    if (exactMatch) {
      // Cari satuan spesifik yang barcode-nya cocok
      let specificUnit = exactMatch.product_units?.find(pu => pu.barcode === barcodeInput.value.trim())

      if (specificUnit) {
        // Jika cocok dengan satuan spesifik, langsung tambahkan
        const success = addToCartDirectly(exactMatch, specificUnit)
        if (success) toast.success(`Ditambahkan: ${exactMatch.name}`)
      } else {
        // Jika scan cocok dengan produk utama, periksa jumlah satuan
        const units = exactMatch.product_units || exactMatch.productUnits || []
        if (units.length > 1) {
          openUnitSelector(exactMatch)
        } else {
          // Jika hanya 1 satuan, gunakan satuan terkecil (default)
          let productUnit = getSortedUnits(exactMatch)[0]
          if (productUnit) {
            const success = addToCartDirectly(exactMatch, productUnit)
            if (success) toast.success(`Ditambahkan: ${exactMatch.name}`)
          } else {
            toast.error('Produk tidak memiliki satuan jual')
          }
        }
      }
    } else {
      toast.error('Barcode/Produk tidak ditemukan')
    }
  } catch (error) {
    console.error('Barcode scan error:', error)
    toast.error('Gagal memproses barcode')
  } finally {
    barcodeInput.value = ''
    isScanning.value = false
    nextTick(() => {
      barcodeInputRef.value?.focus()
    })
  }
}

const openPaymentModal = () => {
  if (!selectedWarehouse.value) {
    toast.error('Pilih gudang terlebih dahulu')
    return
  }
  showPaymentModal.value = true
}

const closePaymentModal = () => {
  showPaymentModal.value = false
}

const handlePaymentSuccess = () => {
  closePaymentModal()
  cartStore.clear()
  loadProducts()
}

// Sync selectedCustomer with cart
watch(selectedCustomer, (val) => {
  if (val) {
    cartStore.setCustomer({
      id: val.id,
      name: val.name,
      phone: val.phone || '',
      address: val.address || ''
    })
  } else {
    cartStore.setCustomer({ id: null, name: '', phone: '', address: '' })
  }
}, { immediate: true })

// Auto focus barcode input when all modals are closed
watch([showProductModal, showUnitSelector, showPaymentModal, showCustomerModal, showHeldModal, showPrinterModal], (vals) => {
  if (vals.every(v => !v)) {
    nextTick(() => {
      barcodeInputRef.value?.focus()
    })
  }
})

onMounted(() => {
  loadWarehouses()
  loadCustomers()
  loadHeldTransactions()
  document.addEventListener('fullscreenchange', onFullscreenChange)
  document.addEventListener('webkitfullscreenchange', onFullscreenChange)
  document.addEventListener('mozfullscreenchange', onFullscreenChange)
  document.addEventListener('MSFullscreenChange', onFullscreenChange)
  
  // Auto focus barcode on initial load
  nextTick(() => {
    barcodeInputRef.value?.focus()
  })
})

onUnmounted(() => {
  document.removeEventListener('fullscreenchange', onFullscreenChange)
  document.removeEventListener('webkitfullscreenchange', onFullscreenChange)
  document.removeEventListener('mozfullscreenchange', onFullscreenChange)
  document.removeEventListener('MSFullscreenChange', onFullscreenChange)
  if (document.fullscreenElement) document.exitFullscreen?.()
})
</script>
