<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Laporan Pembayaran Penjualan</h1>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
        <div>
          <label class="label">Tanggal Mulai</label>
          <input v-model="filters.start_date" type="date" class="input" />
        </div>
        <div>
          <label class="label">Tanggal Akhir</label>
          <input v-model="filters.end_date" type="date" class="input" />
        </div>
        <div>
          <label class="label">Gudang</label>
          <select v-model="filters.warehouse_id" class="input">
            <option value="">Semua Gudang</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
              {{ wh.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="label">Status Pembayaran</label>
          <select v-model="filters.payment_status" class="input">
            <option value="">Semua</option>
            <option value="lunas">Lunas</option>
            <option value="belum_lunas">Belum Lunas</option>
          </select>
        </div>
        <div class="flex items-end gap-2">
          <button @click="loadReport" class="btn btn-primary flex-1">
            <MagnifyingGlassIcon class="w-5 h-5 mr-2" />
            Tampilkan
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Transaksi</p>
        <p class="text-2xl font-bold text-primary-600">{{ summary.total_sales }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Penjualan Kotor</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.total_revenue) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Retur</p>
        <p class="text-2xl font-bold text-red-600">{{ formatCurrency(summary.total_returns ?? 0) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Dibayar</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(summary.total_paid) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Piutang</p>
        <p class="text-2xl font-bold text-amber-600">{{ formatCurrency(summary.total_debt) }}</p>
      </div>
    </div>
    <div v-if="(summary.total_returns ?? 0) > 0" class="mb-6 p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
      <strong>Pendapatan bersih</strong> (setelah retur): {{ formatCurrency(summary.net_revenue ?? summary.total_revenue) }}
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Invoice</th>
              <th class="whitespace-nowrap">Tanggal</th>
              <th class="whitespace-nowrap">Pelanggan</th>
              <th class="whitespace-nowrap text-right">Total</th>
              <th class="whitespace-nowrap text-right">Bayar</th>
              <th class="whitespace-nowrap text-right">Sisa Utang</th>
              <th class="whitespace-nowrap text-right">Kembalian</th>
              <th class="whitespace-nowrap">Metode</th>
              <th class="whitespace-nowrap">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sale in sales" :key="sale.id">
              <td class="font-medium">{{ sale.invoice_number }}</td>
              <td>{{ formatDateTime(sale.created_at || sale.sale_date) }}</td>
              <td>{{ sale.customer_name || 'Umum' }}</td>
              <td class="text-right font-medium">{{ formatCurrency(sale.total) }}</td>
              <td class="text-right">{{ formatCurrency(sale.paid) }}</td>
              <td class="text-right text-amber-600 font-medium">
                {{ formatCurrency(remainingDebt(sale)) }}
              </td>
              <td class="text-right">{{ formatCurrency(sale.change) }}</td>
              <td>
                <span :class="sale.payment_method === 'credit' ? 'badge badge-warning' : 'badge badge-info'">
                  {{ formatPaymentMethod(sale.payment_method) }}
                </span>
              </td>
              <td>
                <span :class="isPaid(sale) ? 'badge badge-success' : 'badge badge-warning'">
                  {{ isPaid(sale) ? 'Lunas' : 'Belum Lunas' }}
                </span>
              </td>
            </tr>
            <tr v-if="!loading && sales.length === 0">
              <td colspan="9" class="text-center text-gray-500 py-8">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatDateTime, formatPaymentMethod } from '@/utils/format'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toast = useToast()

const warehouses = ref([])
const sales = ref([])
const loading = ref(false)
const summary = ref({
  total_sales: 0,
  total_revenue: 0,
  total_paid: 0,
  total_debt: 0
})

const filters = ref({
  start_date: new Date().toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
  warehouse_id: '',
  payment_status: ''
})

const isPaid = (sale) => Number(sale.paid || 0) >= Number(sale.total || 0)

const remainingDebt = (sale) => Math.max(0, Number(sale.total || 0) - Number(sale.paid || 0))

const loadReport = async () => {
  loading.value = true
  try {
    const params = { ...filters.value }
    if (!params.payment_status) delete params.payment_status
    const response = await api.get('/reports/sell-payment', { params })
    sales.value = response.data.sales || []
    summary.value = response.data.summary || { total_sales: 0, total_revenue: 0, total_paid: 0, total_debt: 0 }
  } catch (error) {
    toast.error('Gagal memuat laporan')
  } finally {
    loading.value = false
  }
}

const loadWarehouses = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 100 } })
    warehouses.value = response.data.data || []
  } catch (e) {
    console.error('Error loading warehouses:', e)
  }
}

onMounted(() => {
  loadWarehouses()
  loadReport()
})
</script>
