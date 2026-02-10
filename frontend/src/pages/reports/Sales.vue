<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Laporan Penjualan</h1>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="label">Tanggal Mulai</label>
          <input v-model="filters.start_date" type="date" required class="input" />
        </div>
        <div>
          <label class="label">Tanggal Akhir</label>
          <input v-model="filters.end_date" type="date" required class="input" />
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
        <div class="flex items-end gap-2">
          <button @click="loadReport" class="btn btn-primary flex-1">
            <MagnifyingGlassIcon class="w-5 h-5 mr-2" />
            Tampilkan
          </button>
          <button v-if="hasPermission('export_reports')" @click="exportReport" class="btn btn-success"
            title="Export ke Excel">
            <ArrowDownTrayIcon class="w-5 h-5" />
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
        <p class="text-sm text-gray-600 mb-1">Pendapatan Kotor</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.total_revenue) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Retur</p>
        <p class="text-2xl font-bold text-red-600">{{ formatCurrency(summary.total_returns ?? 0) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Pendapatan Bersih</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(summary.net_revenue ?? summary.total_revenue) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Diskon</p>
        <p class="text-2xl font-bold text-amber-600">{{ formatCurrency(summary.total_discount) }}</p>
      </div>
    </div>

    <!-- Sales Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Invoice</th>
              <th class="whitespace-nowrap">Tanggal</th>
              <th class="whitespace-nowrap">Pelanggan</th>
              <th class="whitespace-nowrap">Subtotal</th>
              <th class="whitespace-nowrap">Diskon</th>
              <th class="whitespace-nowrap">Total</th>
              <th class="whitespace-nowrap">Metode</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sale in sales" :key="sale.id">
              <td class="font-medium">{{ sale.invoice_number }}</td>
              <td>{{ formatDate(sale.sale_date) }}</td>
              <td>{{ sale.customer_name || 'Umum' }}</td>
              <td>{{ formatCurrency(sale.subtotal) }}</td>
              <td class="text-red-600">{{ formatCurrency(sale.discount) }}</td>
              <td class="font-bold">{{ formatCurrency(sale.total) }}</td>
              <td>
                <span :class="sale.payment_method === 'credit' ? 'badge badge-warning' : 'badge badge-info'">
                  {{ formatPaymentMethod(sale.payment_method) }}
                </span>
              </td>
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
import { formatCurrency, formatDate, formatPaymentMethod } from '@/utils/format'
import { MagnifyingGlassIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toast = useToast()

const warehouses = ref([])
const sales = ref([])
const summary = ref({
  total_sales: 0,
  total_revenue: 0,
  total_returns: 0,
  net_revenue: 0,
  total_discount: 0
})

const filters = ref({
  start_date: new Date().toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
  warehouse_id: ''
})

const hasPermission = (permission) => authStore.hasPermission(permission)

const loadReport = async () => {
  try {
    const response = await api.get('/reports/sales', { params: filters.value })
    sales.value = response.data.sales
    summary.value = response.data.summary
  } catch (error) {
    toast.error('Gagal memuat laporan')
  }
}

const exportReport = async () => {
  try {
    const response = await api.get('/reports/export/sales', {
      params: filters.value,
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `laporan-penjualan-${filters.value.start_date}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    toast.success('Laporan berhasil dieksport ke Excel!')
  } catch (error) {
    toast.error('Gagal mengeksport laporan')
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
  loadWarehouses()
  loadReport()
})
</script>
