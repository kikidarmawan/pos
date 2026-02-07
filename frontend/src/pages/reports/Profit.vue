<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Laporan Profit & Keuntungan</h1>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="label">Tanggal Mulai</label>
          <input v-model="filters.start_date" type="date" required class="input" />
        </div>
        <div>
          <label class="label">Tanggal Akhir</label>
          <input v-model="filters.end_date" type="date" required class="input" />
        </div>
        <div class="flex items-end">
          <button @click="loadReport" class="btn btn-primary w-full">
            <MagnifyingGlassIcon class="w-5 h-5 mr-2" />
            Tampilkan
          </button>
        </div>
      </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Pendapatan</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.total_revenue) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Biaya</p>
        <p class="text-2xl font-bold text-red-600">{{ formatCurrency(summary.total_cost) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Keuntungan Kotor</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(summary.gross_profit) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Margin Profit</p>
        <p class="text-2xl font-bold text-purple-600">{{ summary.profit_margin.toFixed(2) }}%</p>
      </div>
    </div>

    <!-- Product Profit Table -->
    <div class="card overflow-hidden">
      <h2 class="text-xl font-bold mb-4 px-6 pt-6">Profit per Produk</h2>
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Produk</th>
              <th class="whitespace-nowrap">Qty Terjual</th>
              <th class="whitespace-nowrap">Pendapatan</th>
              <th class="whitespace-nowrap">Biaya</th>
              <th class="whitespace-nowrap">Profit</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in productProfit" :key="item.product.id">
              <td class="font-medium">{{ item.product.name }}</td>
              <td>{{ item.quantity_sold }}</td>
              <td class="text-green-600">{{ formatCurrency(item.revenue) }}</td>
              <td class="text-red-600">{{ formatCurrency(item.cost) }}</td>
              <td class="font-bold" :class="item.profit >= 0 ? 'text-blue-600' : 'text-red-600'">
                {{ formatCurrency(item.profit) }}
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
import { formatCurrency } from '@/utils/format'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toast = useToast()

const summary = ref({
  total_revenue: 0,
  total_cost: 0,
  gross_profit: 0,
  profit_margin: 0
})
const productProfit = ref([])

const filters = ref({
  start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
  warehouse_id: ''
})

const hasPermission = (permission) => authStore.hasPermission(permission)

const loadReport = async () => {
  try {
    const response = await api.get('/reports/profit', { params: filters.value })
    summary.value = response.data.summary
    productProfit.value = response.data.product_profit
  } catch (error) {
    toast.error('Gagal memuat laporan')
  }
}

onMounted(() => {
  loadReport()
})
</script>
