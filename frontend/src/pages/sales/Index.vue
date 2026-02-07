<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Riwayat Penjualan</h1>

    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <input v-model="filters.search" @input="onSearchInput" type="text" placeholder="Cari invoice atau nama pelanggan..." class="input" />
        <select v-model="filters.warehouse_id" @change="loadSales(1)" class="input">
          <option value="">Semua Gudang</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
            {{ wh.name }}
          </option>
        </select>
        <select v-model="filters.payment_method" @change="loadSales(1)" class="input">
          <option value="">Semua Metode</option>
          <option v-for="pm in paymentMethodOptions" :key="pm.value" :value="pm.value">
            {{ pm.label }}
          </option>
        </select>
        <select v-model="filters.payment_status" @change="loadSales(1)" class="input">
          <option value="">Semua Pembayaran</option>
          <option value="lunas">Lunas</option>
          <option value="belum_lunas">Belum Lunas</option>
        </select>
        <input v-model="filters.start_date" @change="loadSales(1)" type="date" class="input" />
      </div>
    </div>

    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Invoice</th>
              <th class="whitespace-nowrap">Tanggal & Jam</th>
              <th class="whitespace-nowrap">Pelanggan</th>
              <th class="whitespace-nowrap">Total</th>
              <th class="whitespace-nowrap">Metode</th>
              <th class="whitespace-nowrap">Pembayaran</th>
              <th class="whitespace-nowrap">Status</th>
              <th class="whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sale in sales.data" :key="sale.id">
              <td class="font-medium">{{ sale.invoice_number }}</td>
              <td>{{ formatDateTime(sale.created_at || sale.sale_date) }}</td>
              <td>
                <span class="font-medium">{{ sale.customer_name || 'Umum' }}</span>
                <p v-if="sale.customer_address" class="text-xs text-gray-500 mt-0.5">{{ sale.customer_address }}</p>
              </td>
              <td>{{ formatCurrency(sale.total) }}</td>
              <td>
                <span :class="sale.payment_method === 'credit' ? 'badge badge-warning' : 'badge badge-info'">
                  {{ formatPaymentMethod(sale.payment_method) }}
                </span>
              </td>
              <td>
                <template v-if="isPaid(sale)">
                  <span class="badge badge-success">Lunas</span>
                </template>
                <template v-else>
                  <span class="badge badge-warning">Belum Lunas</span>
                  <p class="text-xs text-amber-600 mt-0.5">Sisa {{ formatCurrency(remainingDebt(sale)) }}</p>
                </template>
              </td>
              <td>
                <span :class="{
                  'badge badge-success': sale.status === 'completed',
                  'badge badge-danger': sale.status === 'cancelled'
                }">
                  {{ sale.status === 'completed' ? 'Selesai' : 'Dibatalkan' }}
                </span>
              </td>
              <td>
                <button type="button" @click="openDetail(sale.id)"
                  class="btn btn-sm btn-outline">
                  Detail
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="sales.last_page > 1" class="px-4 py-3 border-t flex flex-wrap items-center justify-between gap-2">
        <div class="text-sm text-gray-600">
          Menampilkan {{ (sales.current_page - 1) * sales.per_page + 1 }} -
          {{ Math.min(sales.current_page * sales.per_page, sales.total) }} dari {{ sales.total }} data
        </div>
        <div class="flex items-center gap-2">
          <select v-model="filters.per_page" @change="loadSales(1)" class="input py-1.5 text-sm w-20">
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
          <span class="text-sm text-gray-500">per halaman</span>
          <div class="flex gap-1 ml-2">
            <button type="button" @click="loadSales(sales.current_page - 1)" :disabled="sales.current_page <= 1"
              class="btn btn-sm btn-outline py-1 px-2">
              &laquo;
            </button>
            <button v-for="p in paginationPages" :key="p" type="button"
              @click="p !== '...' && loadSales(p)"
              :class="['btn btn-sm py-1 px-2', p === sales.current_page ? 'btn-primary' : 'btn-outline']"
              :disabled="p === '...'">
              {{ p }}
            </button>
            <button type="button" @click="loadSales(sales.current_page + 1)"
              :disabled="sales.current_page >= sales.last_page"
              class="btn btn-sm btn-outline py-1 px-2">
              &raquo;
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <SaleDetailModal v-if="selectedSaleId" :sale-id="selectedSaleId"
  @close="selectedSaleId = null" @updated="loadSales" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatDateTime, formatPaymentMethod } from '@/utils/format'
import SaleDetailModal from '@/components/SaleDetailModal.vue'

const toast = useToast()
const sales = ref({ data: [] })
const warehouses = ref([])
const selectedSaleId = ref(null)

const openDetail = (id) => {
  selectedSaleId.value = id
}

const paymentMethodOptions = [
  { value: 'cash', label: 'Tunai' },
  { value: 'card', label: 'Kartu' },
  { value: 'transfer', label: 'Transfer' },
  { value: 'credit', label: 'Utang' },
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

onMounted(() => {
  loadSales()
  loadWarehouses()
})
</script>
