<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Laporan Pembelian & Penjualan</h1>

    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="label">Tanggal Mulai</label>
          <input v-model="filters.start_date" type="date" class="input" />
        </div>
        <div>
          <label class="label">Tanggal Akhir</label>
          <input v-model="filters.end_date" type="date" class="input" />
        </div>
        <div class="flex items-end">
          <button @click="loadReport" class="btn btn-primary w-full">
            <MagnifyingGlassIcon class="w-5 h-5 mr-2" />
            Tampilkan
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div class="card">
        <h2 class="text-lg font-semibold mb-4">Ringkasan Pembelian</h2>
        <p class="text-sm text-gray-600 mb-1">Total Transaksi</p>
        <p class="text-2xl font-bold text-blue-600">{{ purchaseSummary.total_purchases }}</p>
        <p class="text-sm text-gray-600 mb-1 mt-3">Total Nilai</p>
        <p class="text-xl font-bold text-gray-900">{{ formatCurrency(purchaseSummary.total_amount) }}</p>
      </div>
      <div class="card">
        <h2 class="text-lg font-semibold mb-4">Ringkasan Penjualan</h2>
        <p class="text-sm text-gray-600 mb-1">Total Transaksi</p>
        <p class="text-2xl font-bold text-green-600">{{ salesSummary.total_sales }}</p>
        <p class="text-sm text-gray-600 mb-1 mt-3">Total Pendapatan</p>
        <p class="text-xl font-bold text-gray-900">{{ formatCurrency(salesSummary.total_revenue) }}</p>
      </div>
    </div>

    <div class="card overflow-hidden">
      <h2 class="text-lg font-semibold mb-4 px-6 pt-6">Perbandingan</h2>
      <div class="px-6 pb-6">
        <p class="text-gray-600">Selisih: {{ formatCurrency(salesSummary.total_revenue - purchaseSummary.total_amount) }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import api from '@/utils/axios'
import { formatCurrency } from '@/utils/format'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const toast = useToast()
const authStore = useAuthStore()
const purchaseSummary = ref({ total_purchases: 0, total_amount: 0 })
const salesSummary = ref({ total_sales: 0, total_revenue: 0 })

const filters = reactive({
  start_date: new Date().toISOString().slice(0, 7) + '-01',
  end_date: new Date().toISOString().slice(0, 10),
})

const hasPermission = (p) => authStore.hasPermission(p)

const loadReport = async () => {
  try {
    const [pRes, sRes] = await Promise.all([
      api.get('/reports/purchases', { params: filters }),
      api.get('/reports/sales', { params: filters }),
    ])
    purchaseSummary.value = pRes.data.summary
    salesSummary.value = sRes.data.summary
  } catch (e) {
    toast.error('Gagal memuat laporan')
  }
}

onMounted(loadReport)
</script>
