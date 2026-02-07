<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Laporan Pembelian</h1>

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
          <label class="label">Supplier</label>
          <select v-model="filters.supplier_id" class="input">
            <option value="">Semua Supplier</option>
            <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">
              {{ sup.name }}
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

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Pembelian</p>
        <p class="text-2xl font-bold text-primary-600">{{ summary.total_purchases }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Pengeluaran</p>
        <p class="text-2xl font-bold text-red-600">{{ formatCurrency(summary.total_amount) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Ongkir</p>
        <p class="text-2xl font-bold text-yellow-600">{{ formatCurrency(summary.total_shipping) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Gross Amount</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.gross_amount) }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Invoice</th>
              <th class="whitespace-nowrap">Tanggal</th>
              <th class="whitespace-nowrap">Supplier</th>
              <th class="whitespace-nowrap">Subtotal</th>
              <th class="whitespace-nowrap">Ongkir</th>
              <th class="whitespace-nowrap">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="purchase in purchases" :key="purchase.id">
              <td class="font-medium">{{ purchase.invoice_number }}</td>
              <td>{{ formatDate(purchase.purchase_date) }}</td>
              <td>{{ purchase.supplier?.name }}</td>
              <td>{{ formatCurrency(purchase.subtotal) }}</td>
              <td>{{ formatCurrency(purchase.shipping_cost) }}</td>
              <td class="font-bold">{{ formatCurrency(purchase.total) }}</td>
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
import { formatCurrency, formatDate } from '@/utils/format'
import { MagnifyingGlassIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toast = useToast()

const suppliers = ref([])
const purchases = ref([])
const summary = ref({
  total_purchases: 0,
  total_amount: 0,
  total_shipping: 0,
  gross_amount: 0
})

const filters = ref({
  start_date: new Date().toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
  supplier_id: ''
})

const hasPermission = (permission) => authStore.hasPermission(permission)

const loadReport = async () => {
  try {
    const response = await api.get('/reports/purchases', { params: filters.value })
    purchases.value = response.data.purchases
    summary.value = response.data.summary
  } catch (error) {
    toast.error('Gagal memuat laporan')
  }
}

const exportReport = async () => {
  try {
    const response = await api.get('/reports/export/purchases', {
      params: filters.value,
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `laporan-pembelian-${filters.value.start_date}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    toast.success('Laporan berhasil dieksport ke Excel!')
  } catch (error) {
    toast.error('Gagal mengeksport laporan')
  }
}

const loadSuppliers = async () => {
  try {
    const response = await api.get('/suppliers', { params: { per_page: 100 } })
    suppliers.value = response.data.data
  } catch (error) {
    console.error('Error loading suppliers:', error)
  }
}

onMounted(() => {
  loadSuppliers()
  loadReport()
})
</script>
