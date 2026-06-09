<template>
  <div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Riwayat Penjualan</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola dan pantau semua transaksi penjualan Anda secara real-time.</p>
      </div>
    </div>

    <!-- Quick Stats Summary (Premium addition) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-2xl p-5 shadow-lg shadow-indigo-100 flex items-center justify-between">
        <div>
          <span class="text-sm font-medium text-indigo-100">Total Transaksi</span>
          <h3 class="text-3xl font-bold mt-1">{{ sales.total || 0 }}</h3>
        </div>
        <div class="bg-white/10 p-3 rounded-xl backdrop-blur-md">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
        </div>
      </div>

      <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-2xl p-5 shadow-lg shadow-emerald-100 flex items-center justify-between">
        <div>
          <span class="text-sm font-medium text-emerald-100 font-sans">Estimasi Penerimaan</span>
          <h3 class="text-3xl font-bold mt-1">{{ formatCurrency(totalRevenue) }}</h3>
        </div>
        <div class="bg-white/10 p-3 rounded-xl backdrop-blur-md">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-2xl p-5 shadow-lg shadow-amber-100 flex items-center justify-between">
        <div>
          <span class="text-sm font-medium text-amber-100">Belum Lunas (Piutang)</span>
          <h3 class="text-3xl font-bold mt-1">{{ formatCurrency(totalDebt) }}</h3>
        </div>
        <div class="bg-white/10 p-3 rounded-xl backdrop-blur-md">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
      <div class="flex items-center gap-2 text-gray-700 font-semibold text-sm">
        <FunnelIcon class="w-4 h-4 text-gray-400" />
        Filter Pencarian
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Search Input -->
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
          </div>
          <input 
            v-model="filters.search" 
            @input="onSearchInput" 
            type="text" 
            placeholder="Cari invoice/pelanggan..." 
            class="input pl-9 w-full bg-gray-50 border-gray-200 focus:bg-white text-sm" 
          />
        </div>

        <!-- Warehouse Dropdown -->
        <div class="relative">
          <select v-model="filters.warehouse_id" @change="loadSales(1)" class="input w-full bg-gray-50 border-gray-200 text-sm">
            <option value="">Semua Gudang</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
              {{ wh.name }}
            </option>
          </select>
        </div>

        <!-- Payment Method Dropdown -->
        <div class="relative">
          <select v-model="filters.payment_method" @change="loadSales(1)" class="input w-full bg-gray-50 border-gray-200 text-sm">
            <option value="">Semua Metode</option>
            <option v-for="pm in paymentMethodOptions" :key="pm.value" :value="pm.value">
              {{ pm.label }}
            </option>
          </select>
        </div>

        <!-- Payment Status Dropdown -->
        <div class="relative">
          <select v-model="filters.payment_status" @change="loadSales(1)" class="input w-full bg-gray-50 border-gray-200 text-sm">
            <option value="">Semua Pembayaran</option>
            <option value="lunas">Lunas</option>
            <option value="belum_lunas">Belum Lunas</option>
          </select>
        </div>

        <!-- Start Date Picker -->
        <div class="relative">
          <input 
            v-model="filters.start_date" 
            @change="loadSales(1)" 
            type="date" 
            class="input w-full bg-gray-50 border-gray-200 text-sm" 
          />
        </div>
      </div>
    </div>

    <!-- Sales Table Section -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/75 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <th class="py-4 px-6">Invoice</th>
              <th class="py-4 px-6">Tanggal & Jam</th>
              <th class="py-4 px-6">Pelanggan</th>
              <th class="py-4 px-6 text-right">Total</th>
              <th class="py-4 px-6">Metode</th>
              <th class="py-4 px-6">Pembayaran</th>
              <th class="py-4 px-6">Status</th>
              <th class="py-4 px-6 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-sm">
            <tr v-if="sales.data.length === 0" class="hover:bg-gray-50/50 transition-colors">
              <td colspan="8" class="py-12 text-center text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Belum ada transaksi penjualan yang terdaftar.
              </td>
            </tr>
            <tr v-for="sale in sales.data" :key="sale.id" class="hover:bg-gray-50/50 transition-colors group">
              <!-- Invoice Number -->
              <td class="py-4 px-6 font-medium text-gray-900">{{ sale.invoice_number }}</td>
              
              <!-- Date & Time -->
              <td class="py-4 px-6 text-gray-600">
                {{ formatDateTime(sale.created_at || sale.sale_date) }}
              </td>
              
              <!-- Customer Info with Inline Edit option -->
              <td class="py-4 px-6">
                <div class="flex items-center gap-2 group-hover:translate-x-0.5 transition-transform duration-200">
                  <span class="font-medium text-gray-900">{{ sale.customer_name || 'Umum' }}</span>
                  <button 
                    v-if="!sale.is_customer_edited && sale.status !== 'cancelled'" 
                    type="button" 
                    @click="openEditCustomer(sale)"
                    class="text-gray-400 hover:text-indigo-600 transition-opacity opacity-0 group-hover:opacity-100 focus:opacity-100 p-0.5"
                    title="Ubah pelanggan"
                  >
                    <PencilSquareIcon class="w-4 h-4" />
                  </button>
                  <span 
                    v-else-if="sale.is_customer_edited" 
                    class="text-[10px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 shrink-0 font-medium scale-90"
                    title="Sudah pernah diubah"
                  >
                    1x
                  </span>
                </div>
                <p v-if="sale.customer_address" class="text-xs text-gray-400 mt-1 max-w-[200px] truncate" :title="sale.customer_address">
                  {{ sale.customer_address }}
                </p>
              </td>
              
              <!-- Total Amount -->
              <td class="py-4 px-6 text-right font-semibold text-gray-900">
                {{ formatCurrency(sale.total) }}
              </td>
              
              <!-- Payment Method -->
              <td class="py-4 px-6">
                <span 
                  :class="[
                    'px-2.5 py-1 rounded-full text-xs font-medium border',
                    sale.payment_method === 'credit' 
                      ? 'bg-amber-50 text-amber-700 border-amber-200' 
                      : 'bg-indigo-50 text-indigo-700 border-indigo-100'
                  ]"
                >
                  {{ formatPaymentMethod(sale.payment_method) }}
                </span>
              </td>
              
              <!-- Payment Status -->
              <td class="py-4 px-6">
                <template v-if="isPaid(sale)">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Lunas
                  </span>
                </template>
                <template v-else>
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                    Belum Lunas
                  </span>
                  <p class="text-[11px] text-rose-500 mt-1 font-medium">Sisa {{ formatCurrency(remainingDebt(sale)) }}</p>
                </template>
              </td>
              
              <!-- Transaction Status -->
              <td class="py-4 px-6">
                <span 
                  :class="[
                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border',
                    sale.status === 'completed' 
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-100' 
                      : 'bg-gray-50 text-gray-500 border-gray-200'
                  ]"
                >
                  {{ sale.status === 'completed' ? 'Selesai' : 'Dibatalkan' }}
                </span>
              </td>
              
              <!-- Actions -->
              <td class="py-4 px-6 text-center">
                <button 
                  type="button" 
                  @click="openDetail(sale.id)"
                  class="inline-flex items-center px-3 py-1.5 border border-gray-200 text-xs font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all"
                >
                  Detail
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="sales.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-gray-50/50">
        <div class="text-xs text-gray-500 font-medium">
          Menampilkan <span class="text-gray-900">{{ (sales.current_page - 1) * sales.per_page + 1 }}</span> -
          <span class="text-gray-900">{{ Math.min(sales.current_page * sales.per_page, sales.total) }}</span> dari <span class="text-gray-900">{{ sales.total }}</span> data
        </div>
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5 text-xs text-gray-500">
            <span>Tampilkan</span>
            <select v-model="filters.per_page" @change="loadSales(1)" class="bg-white border border-gray-200 rounded-lg py-1 px-2 text-xs text-gray-900 focus:outline-none focus:ring-1 focus:ring-indigo-500">
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
          </div>
          <div class="flex gap-1.5">
            <button 
              type="button" 
              @click="loadSales(sales.current_page - 1)" 
              :disabled="sales.current_page <= 1"
              class="inline-flex items-center px-2 py-1.5 border border-gray-200 rounded-lg bg-white text-xs font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:hover:bg-white transition-all"
            >
              &laquo;
            </button>
            <button 
              v-for="p in paginationPages" 
              :key="p" 
              type="button"
              @click="p !== '...' && loadSales(p)"
              :class="[
                'inline-flex items-center px-3 py-1.5 border text-xs font-medium rounded-lg transition-all', 
                p === sales.current_page 
                  ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm shadow-indigo-100' 
                  : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50'
              ]"
              :disabled="p === '...'"
            >
              {{ p }}
            </button>
            <button 
              type="button" 
              @click="loadSales(sales.current_page + 1)"
              :disabled="sales.current_page >= sales.last_page"
              class="inline-flex items-center px-2 py-1.5 border border-gray-200 rounded-lg bg-white text-xs font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:hover:bg-white transition-all"
            >
              &raquo;
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <SaleDetailModal 
      v-if="selectedSaleId" 
      :sale-id="selectedSaleId"
      @close="selectedSaleId = null" 
      @updated="loadSales" 
    />

    <!-- Edit Customer Modal (Directly from Index) -->
    <div v-if="editingSaleCustomer" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeEditCustomer">
      <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
          <div>
            <h3 class="font-bold text-gray-900 text-lg">Ubah Pelanggan</h3>
            <p class="text-xs text-gray-500 mt-0.5">Invoice: <span class="font-medium text-gray-800">{{ editingSaleCustomer.invoice_number }}</span></p>
          </div>
          <button type="button" @click="closeEditCustomer" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Pilih Pelanggan Terdaftar</label>
            <select 
              :value="customerEditForm.customer_id || ''" 
              @change="onModalCustomerSelect" 
              class="input text-sm w-full border-gray-200 focus:ring-indigo-500 focus:border-indigo-500"
              :disabled="customerListLoading"
            >
              <option value="">-- Umum / Non-Member --</option>
              <option v-for="c in customerList" :key="c.id" :value="c.id">
                {{ c.name }} ({{ c.phone || 'No telp -' }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-1 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Nama Pelanggan</label>
              <input v-model="customerEditForm.customer_name" type="text" class="input text-sm w-full border-gray-200 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Nama pelanggan" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">No. Telepon</label>
              <input v-model="customerEditForm.customer_phone" type="text" class="input text-sm w-full border-gray-200 focus:ring-indigo-500 focus:border-indigo-500" placeholder="No. telepon" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Alamat</label>
            <textarea v-model="customerEditForm.customer_address" class="input text-sm w-full h-20 resize-none border-gray-200 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Alamat pelanggan"></textarea>
          </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex gap-3 justify-end">
          <button type="button" @click="closeEditCustomer" class="btn btn-outline flex-1 sm:flex-initial">
            Batal
          </button>
          <button type="button" @click="saveSaleCustomer" :disabled="saveCustomerLoading" class="btn btn-primary flex-1 sm:flex-initial bg-indigo-600 hover:bg-indigo-700 text-white">
            {{ saveCustomerLoading ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatDateTime, formatPaymentMethod } from '@/utils/format'
import SaleDetailModal from '@/components/SaleDetailModal.vue'
import { 
  FunnelIcon, 
  MagnifyingGlassIcon, 
  PencilSquareIcon, 
  XMarkIcon 
} from '@heroicons/vue/24/outline'

const toast = useToast()
const sales = ref({ data: [] })
const warehouses = ref([])
const selectedSaleId = ref(null)

// Direct edit customer states
const editingSaleCustomer = ref(null)
const customerList = ref([])
const customerListLoading = ref(false)
const saveCustomerLoading = ref(false)
const customerEditForm = ref({
  customer_id: null,
  customer_name: '',
  customer_phone: '',
  customer_address: ''
})

const openDetail = (id) => {
  selectedSaleId.value = id
}

// Stats calculation
const totalRevenue = computed(() => {
  return sales.value.data?.reduce((sum, item) => sum + Number(item.paid || 0), 0) || 0
})

const totalDebt = computed(() => {
  return sales.value.data?.reduce((sum, item) => {
    if (item.status === 'cancelled') return sum
    const debt = Number(item.total || 0) - Number(item.paid || 0)
    return sum + Math.max(0, debt)
  }, 0) || 0
})

const paymentMethodOptions = [
  { value: 'cash', label: 'Tunai' },
  { value: 'card', label: 'Kartu' },
  { value: 'transfer', label: 'Transfer' },
  { value: 'credit', label: 'Utang' },
  { value: 'cod', label: 'COD' },
  { value: 'other', label: 'Lainnya' },
]

const filters = ref({
  search: '',
  warehouse_id: '',
  payment_method: '',
  payment_status: '',
  start_date: '',
  per_page: 15
})

let searchTimeout = null
const onSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadSales(1), 400)
}

const paginationPages = computed(() => {
  const cur = sales.value.current_page || 1
  const last = sales.value.last_page || 1
  if (last <= 7) return [...Array(last)].map((_, i) => i + 1)
  if (cur <= 4) return [1, 2, 3, 4, 5, '...', last]
  if (cur >= last - 3) return [1, '...', last - 4, last - 3, last - 2, last - 1, last]
  return [1, '...', cur - 1, cur, cur + 1, '...', last]
})

const isPaid = (sale) => Number(sale.paid || 0) >= Number(sale.total || 0)

const remainingDebt = (sale) => Math.max(0, Number(sale.total || 0) - Number(sale.paid || 0))

const loadSales = async (page) => {
  try {
    const params = { ...filters.value }
    params.page = page ?? sales.value?.current_page ?? 1
    const response = await api.get('/sales', { params })
    sales.value = response.data
  } catch (error) {
    toast.error('Gagal memuat penjualan')
  }
}

const loadWarehouses = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 100 } })
    warehouses.value = response.data.data
  } catch (error) {
    console.error('Error loading warehouses:', error)
  }
}

// Edit Customer Modal Handlers
const openEditCustomer = async (saleItem) => {
  editingSaleCustomer.value = saleItem
  customerEditForm.value = {
    customer_id: saleItem.customer_id,
    customer_name: saleItem.customer_name || '',
    customer_phone: saleItem.customer_phone || '',
    customer_address: saleItem.customer_address || ''
  }
  
  // Load customers
  if (customerList.value.length === 0) {
    customerListLoading.value = true
    try {
      const res = await api.get('/customers', { params: { per_page: 200, is_active: 1 } })
      customerList.value = res.data.data || []
    } catch (e) {
      console.error('Gagal memuat daftar pelanggan', e)
    } finally {
      customerListLoading.value = false
    }
  }
}

const closeEditCustomer = () => {
  editingSaleCustomer.value = null
}

const onModalCustomerSelect = (event) => {
  const customerId = event.target.value
  if (!customerId) {
    customerEditForm.value.customer_id = null
    customerEditForm.value.customer_name = 'Umum'
    customerEditForm.value.customer_phone = ''
    customerEditForm.value.customer_address = ''
  } else {
    const cust = customerList.value.find(c => c.id == customerId)
    if (cust) {
      customerEditForm.value.customer_id = cust.id
      customerEditForm.value.customer_name = cust.name
      customerEditForm.value.customer_phone = cust.phone || ''
      customerEditForm.value.customer_address = cust.address || ''
    }
  }
}

const saveSaleCustomer = async () => {
  if (!editingSaleCustomer.value) return
  saveCustomerLoading.value = true
  try {
    await api.put(`/sales/${editingSaleCustomer.value.id}/customer`, customerEditForm.value)
    toast.success('Data pelanggan berhasil diubah')
    closeEditCustomer()
    await loadSales()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal mengubah data pelanggan')
  } finally {
    saveCustomerLoading.value = false
  }
}

onMounted(() => {
  loadSales()
  loadWarehouses()
})
</script>
