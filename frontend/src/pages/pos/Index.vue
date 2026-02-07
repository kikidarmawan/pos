<template>
  <div ref="posContainerRef" class="pos-page min-h-[calc(100dvh-6rem)] lg:min-h-[calc(100dvh-5rem)] pb-20">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6 items-stretch min-h-0">
    <!-- Left: Product Selection -->
    <div class="lg:col-span-2 space-y-3 min-h-0 flex flex-col overflow-hidden order-2 lg:order-1">
      <!-- Customer (compact) -->
      <div class="card p-3">
        <div class="flex flex-wrap gap-2 items-end">
          <div class="flex-1 min-w-[140px]">
            <label class="label text-xs py-0.5">Pelanggan</label>
            <div class="flex gap-1">
              <VSelect
                v-model="selectedCustomer"
                :options="customers"
                :reduce="(c) => c"
                label="name"
                placeholder="Pilih pelanggan..."
                :filterable="true"
                :clearable="true"
                class="flex-1 vue-select-compact"
              />
              <button type="button" @click="showCustomerModal = true" class="btn btn-primary btn-sm shrink-0 p-2"
                title="Tambah Pelanggan Baru">
                <PlusIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
          <div class="w-32">
            <label class="label text-xs py-0.5">Nama</label>
            <input v-model="cartStore.customer.name" type="text" class="input input-sm py-1.5"
              :placeholder="selectedCustomer ? '' : 'Walk-in'" />
          </div>
          <div class="w-28">
            <label class="label text-xs py-0.5">No. HP</label>
            <input v-model="cartStore.customer.phone" type="text" class="input input-sm py-1.5"
              placeholder="08xxx" />
          </div>
          <div class="flex-1 min-w-[120px]">
            <label class="label text-xs py-0.5">Alamat</label>
            <input v-model="cartStore.customer.address" type="text" class="input input-sm py-1.5"
              placeholder="Alamat (opsional)" />
          </div>
        </div>
      </div>

      <!-- Cari Produk -->
      <div class="card">
        <div class="flex gap-3 items-center">
          <input v-model="search" @input="searchProducts" type="text" placeholder="Cari produk (nama, kode, barcode)..."
            class="input flex-1" autofocus />
          <select v-model="selectedWarehouse" required class="input w-48" @change="loadProducts">
            <option value="">Pilih Gudang</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
              {{ wh.name }}
            </option>
          </select>
          <button type="button" @click="toggleFullscreen"
            class="p-2.5 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 hover:text-primary-600 transition-colors shrink-0"
            :title="isFullscreen ? 'Keluar Fullscreen (ESC)' : 'Fullscreen'">
            <ArrowsPointingOutIcon v-if="!isFullscreen" class="w-5 h-5" />
            <ArrowsPointingInIcon v-else class="w-5 h-5" />
          </button>
          <button type="button" @click="connectPrinter" :disabled="printerConnecting"
            class="p-2.5 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 hover:text-primary-600 transition-colors shrink-0 flex items-center gap-1.5"
            :title="printerReady ? 'Printer thermal siap' : 'Sambungkan XPrinter 58IIZ'">
            <PrinterIcon class="w-5 h-5" :class="printerReady ? 'text-green-600' : ''" />
            <span v-if="printerReady" class="text-xs text-green-600 hidden sm:inline">Siap</span>
            <span v-else class="text-xs hidden sm:inline">{{ printerConnecting ? '...' : 'Printer' }}</span>
          </button>
        </div>
      </div>

      <!-- Products Grid -->
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
      <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 flex-1 min-h-0 overflow-y-auto content-start">
        <div v-for="product in products" :key="product.id" @click="openUnitSelector(product)"
          class="card cursor-pointer hover:shadow-lg transition-shadow duration-200 p-4">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-2 bg-gray-200 rounded-lg flex items-center justify-center">
              <CubeIcon class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="font-medium text-sm mb-1 line-clamp-2">{{ product.name }}</h3>
            <p class="text-xs text-gray-500 mb-2">{{ product.code }}</p>
            <p class="text-xs text-gray-500 mb-1">Satuan: {{ product.base_unit?.name || product.baseUnit?.name || '-' }}</p>
            <p class="font-bold text-primary-600">{{ formatCurrency(product.base_price) }}</p>
            <p class="text-xs" :class="(product.total_stock || 0) < (product.minimum_stock || 0) ? 'text-red-600 font-medium' : 'text-gray-500'">
              Stok: {{ formatStock(product.total_stock ?? 0) }} {{ product.base_unit?.name || product.baseUnit?.name || '' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Cart - sticky, height accounts for action bar at bottom, responsive -->
    <div class="lg:col-span-1 flex flex-col min-h-[320px] sm:min-h-[360px] max-h-[calc(55dvh-5rem)] sm:max-h-[calc(60dvh-5rem)] lg:max-h-none lg:h-[calc(100dvh-12rem)] lg:sticky lg:top-20 order-1 lg:order-2 lg:min-h-0">
      <div class="card flex flex-col flex-1 min-h-0 overflow-hidden h-full">
        <div class="flex items-center justify-between mb-4 shrink-0">
          <h2 class="text-xl font-bold">Keranjang</h2>
          <button v-if="isFullscreen" type="button" @click="toggleFullscreen"
            class="p-2 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600 hover:text-primary-600 transition-colors"
            title="Keluar Fullscreen">
            <ArrowsPointingInIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Cart Items - scrollable -->
        <div class="space-y-2 mb-4 flex-1 min-h-0 overflow-y-auto overscroll-contain">
          <div v-for="(item, index) in cartStore.items" :key="index" class="bg-gray-50 rounded-lg p-3">
            <div class="flex justify-between items-start mb-2">
              <div class="flex-1">
                <h4 class="font-medium text-sm">{{ item.product.name }}</h4>
                <p class="text-xs text-gray-500">{{ item.unit.name }}</p>
              </div>
              <button @click="cartStore.removeItem(index)" class="text-red-600 hover:text-red-800">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-1">
                <button @click="cartStore.updateQuantity(index, item.quantity - 1)"
                  class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">
                  -
                </button>
                <input :value="item.quantity" @change="cartStore.updateQuantity(index, parseFloat($event.target.value))"
                  type="number" step="0.01" class="w-16 text-center border rounded px-2 py-1" />
                <button @click="cartStore.updateQuantity(index, item.quantity + 1)"
                  class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">
                  +
                </button>
              </div>
              <div class="text-right">
                <template v-if="(item.discount || 0) > 0">
                  <p class="text-xs text-gray-400 line-through">{{ formatCurrency((item.price * item.quantity)) }}</p>
                  <p class="text-sm font-bold text-green-600">{{ formatCurrency(item.subtotal) }}</p>
                </template>
                <template v-else>
                  <p class="text-sm font-bold">{{ formatCurrency(item.subtotal) }}</p>
                </template>
                <p class="text-xs text-gray-500">@{{ formatCurrency(item.price) }}</p>
              </div>
            </div>
            <div class="mt-2 flex items-center gap-2">
              <label class="text-xs text-gray-500 whitespace-nowrap">Diskon/qty:</label>
              <input
                :value="item.discount ?? 0"
                @input="cartStore.updateItemDiscount(index, ($event.target).value)"
                type="number"
                step="100"
                min="0"
                class="w-24 text-sm border rounded px-2 py-1"
                placeholder="0"
                title="Diskon per satuan"
              />
              <span v-if="(item.discount || 0) > 0" class="text-xs text-gray-400">
                = {{ formatCurrency((item.discount || 0) * item.quantity) }}
              </span>
            </div>
          </div>

          <div v-if="!cartStore.items.length" class="text-center text-gray-500 py-8">
            Keranjang masih kosong
          </div>
        </div>

        <!-- Summary -->
        <div class="space-y-2 py-4 border-t border-b shrink-0">
          <div class="flex justify-between text-sm">
            <span>Subtotal:</span>
            <span class="font-medium">{{ formatCurrency(cartStore.subtotal) }}</span>
          </div>
          <div class="flex justify-between items-center text-sm">
            <span>Diskon:</span>
            <input v-model="cartStore.discount" type="number" step="1000"
              class="w-32 text-right border rounded px-2 py-1" />
          </div>
          <div class="flex justify-between items-center text-sm">
            <span>Pajak (%):</span>
            <input v-model="cartStore.tax" type="number" step="1" class="w-32 text-right border rounded px-2 py-1" />
          </div>
          <div class="flex justify-between text-lg font-bold pt-2">
            <span>Total:</span>
            <span class="text-primary-600">{{ formatCurrency(cartStore.total) }}</span>
          </div>
        </div>
      </div>
    </div>
    </div>

    <!-- Action Buttons - fixed bottom, horizontal, full width (not in sidebar; left-0 when fullscreen) -->
    <div class="fixed bottom-0 left-0 right-0 z-20 bg-white border-t shadow-lg px-4 py-3"
      :class="isFullscreen ? '' : 'lg:left-64'">
      <div class="flex flex-wrap gap-4 items-center justify-between max-w-7xl mx-auto">
        <div class="flex items-baseline gap-4 shrink-0">
          <span class="text-sm text-gray-600">Total:</span>
          <span class="text-xl font-bold text-primary-600">{{ formatCurrency(cartStore.total) }}</span>
        </div>
        <div class="flex flex-wrap gap-2 justify-end">
        <button @click="openPaymentModal" :disabled="!cartStore.items.length || !selectedWarehouse"
          class="btn btn-success">
          <CurrencyDollarIcon class="w-5 h-5 mr-2" />
          Bayar
        </button>
        <button @click="holdTransaction" :disabled="!cartStore.items.length || !selectedWarehouse"
          class="btn btn-secondary">
          <ClockIcon class="w-5 h-5 mr-2" />
          Tahan
        </button>
        <button @click="openHeldModal" class="btn btn-outline" :class="heldCount > 0 ? 'border-amber-500 text-amber-600' : ''">
          <FolderIcon class="w-5 h-5 mr-2" />
          Transaksi Tertahan ({{ heldCount }})
        </button>
        <button @click="cartStore.clear()" :disabled="!cartStore.items.length" class="btn btn-danger">
          <TrashIcon class="w-5 h-5 mr-2" />
          Hapus Semua
        </button>
        </div>
      </div>
    </div>

    <!-- Unit Selector Modal -->
    <div v-if="showUnitSelector" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold mb-4">Pilih Satuan</h3>
        <div class="space-y-2">
          <button v-for="pu in (selectedProduct?.product_units || selectedProduct?.productUnits || [])" :key="pu.id" @click="addToCart(pu)"
            class="w-full p-4 text-left border-2 rounded-lg hover:border-primary-600 hover:bg-primary-50 transition-colors">
            <div class="flex justify-between items-center">
              <div>
                <p class="font-medium">{{ pu.unit.name }}</p>
                <p class="text-sm text-gray-500">Konversi: {{ pu.conversion_factor }}x</p>
              </div>
              <p class="font-bold text-primary-600">{{ formatCurrency(pu.selling_price) }}</p>
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
          <input v-model="heldSearch" type="text"
            placeholder="Cari nama, no HP, alamat, atau gudang..."
            class="input w-full"
          />
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
  overflow-x: visible !important;
  overflow-y: auto;
  padding: 1rem 1.5rem !important;
  box-sizing: border-box !important;
}
</style>
<style>
/* Non-scoped: fullscreen pseudo-class needs global scope in some browsers */
.pos-page:fullscreen {
  background-color: #f9fafb !important;
}
.pos-page:-webkit-full-screen {
  background-color: #f9fafb !important;
}
.pos-page:-moz-full-screen {
  background-color: #f9fafb !important;
}
.pos-page:-ms-fullscreen {
  background-color: #f9fafb !important;
}
</style>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useCartStore } from '@/stores/cart'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatStock, formatDateTime } from '@/utils/format'
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
  PrinterIcon
} from '@heroicons/vue/24/outline'
import { connectThermalPrinter } from '@/utils/printReceipt'
import PaymentModal from './PaymentModal.vue'
import CustomerFormModal from '@/components/CustomerFormModal.vue'

const cartStore = useCartStore()
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
const loadingProducts = ref(false)
const loadingHolds = ref(false)
const customers = ref([])
const selectedCustomer = ref(null)
const heldTransactions = ref([])
const heldSearch = ref('')
const posContainerRef = ref(null)
const isFullscreen = ref(false)
const printerConnecting = ref(false)
const printerReady = ref(false)

const checkPrinterReady = () => {
  try {
    const s = localStorage.getItem('pos_thermal_printer_device')
    printerReady.value = !!(s && JSON.parse(s)?.vendorId)
  } catch (_) {
    printerReady.value = false
  }
}

const connectPrinter = async () => {
  if (!('serial' in navigator)) {
    toast.error('Gunakan Chrome atau Edge untuk cetak langsung ke printer thermal')
    return
  }
  printerConnecting.value = true
  try {
    const result = await connectThermalPrinter()
    if (result === null) {
      toast.info('Penyambungan printer dibatalkan')
      return
    }
    printerReady.value = true
    toast.success('Printer thermal terhubung. Klik Cetak Struk akan langsung cetak ke printer.')
  } catch (err) {
    if (err.message?.includes('canceled') || err.name === 'NotFoundError') {
      toast.info('Pemilihan printer dibatalkan')
    } else {
      toast.error(err.message || 'Gagal menyambungkan printer')
    }
  } finally {
    printerConnecting.value = false
  }
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

const openUnitSelector = (product) => {
  selectedProduct.value = product
  showUnitSelector.value = true
}

const closeUnitSelector = () => {
  showUnitSelector.value = false
  selectedProduct.value = null
}

const addToCart = (productUnit) => {
  cartStore.addItem(selectedProduct.value, productUnit.unit, productUnit)
  closeUnitSelector()
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

onMounted(() => {
  loadWarehouses()
  loadCustomers()
  loadHeldTransactions()
  document.addEventListener('fullscreenchange', onFullscreenChange)
  document.addEventListener('webkitfullscreenchange', onFullscreenChange)
  document.addEventListener('mozfullscreenchange', onFullscreenChange)
  document.addEventListener('MSFullscreenChange', onFullscreenChange)
})

onUnmounted(() => {
  document.removeEventListener('fullscreenchange', onFullscreenChange)
  document.removeEventListener('webkitfullscreenchange', onFullscreenChange)
  document.removeEventListener('mozfullscreenchange', onFullscreenChange)
  document.removeEventListener('MSFullscreenChange', onFullscreenChange)
  if (document.fullscreenElement) document.exitFullscreen?.()
})
</script>
