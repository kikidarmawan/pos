<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Dashboard</h1>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <div class="card bg-gradient-to-br from-blue-500 to-blue-600 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm opacity-90">Penjualan Hari Ini</p>
            <p class="text-3xl font-bold mt-2">{{ formatCurrency(stats.today_sales) }}</p>
            <p class="text-xs opacity-75 mt-1">{{ stats.today_transactions }} transaksi</p>
          </div>
          <CurrencyDollarIcon class="w-16 h-16 opacity-20" />
        </div>
      </div>

      <div class="card bg-gradient-to-br from-green-500 to-green-600 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm opacity-90">Penjualan Bulan Ini</p>
            <p class="text-3xl font-bold mt-2">{{ formatCurrency(stats.month_sales) }}</p>
          </div>
          <ChartBarIcon class="w-16 h-16 opacity-20" />
        </div>
      </div>

      <div class="card bg-gradient-to-br from-yellow-500 to-yellow-600 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm opacity-90">Produk Stok Rendah</p>
            <p class="text-3xl font-bold mt-2">{{ stats.low_stock_products }}</p>
            <router-link to="/stocks" class="text-xs underline mt-1 block">Lihat Detail</router-link>
          </div>
          <ExclamationTriangleIcon class="w-16 h-16 opacity-20" />
        </div>
      </div>

      <div class="card bg-gradient-to-br from-purple-500 to-purple-600 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm opacity-90">Total Produk</p>
            <p class="text-3xl font-bold mt-2">{{ stats.total_products || 0 }}</p>
          </div>
          <CubeIcon class="w-16 h-16 opacity-20" />
        </div>
      </div>
    </div>

    <!-- Top Products & Recent Sales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Top Selling Products -->
      <div class="card">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Produk Terlaris Bulan Ini</h2>
        <div class="space-y-3">
          <div v-for="product in stats.top_products" :key="product.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex-1">
              <p class="font-medium text-gray-900">{{ product.name }}</p>
              <p class="text-sm text-gray-500">Terjual: {{ formatStock(product.total_sold ?? 0) }}</p>
            </div>
            <p class="font-bold text-primary-600">{{ formatCurrency(product.total_revenue) }}</p>
          </div>
          <div v-if="!stats.top_products?.length" class="text-center text-gray-500 py-8">
            Belum ada data
          </div>
        </div>
      </div>

      <!-- Recent Sales -->
      <div class="card">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Transaksi Terbaru</h2>
        <div class="space-y-3">
          <div v-for="sale in stats.recent_sales" :key="sale.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex-1">
              <p class="font-medium text-gray-900">{{ sale.invoice_number }}</p>
              <p class="text-sm text-gray-500">
                {{ sale.customer_name || 'Umum' }} | {{ formatDate(sale.sale_date) }}
              </p>
            </div>
            <p class="font-bold text-green-600">{{ formatCurrency(sale.total) }}</p>
          </div>
          <div v-if="!stats.recent_sales?.length" class="text-center text-gray-500 py-8">
            Belum ada transaksi
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/utils/axios'
import { formatCurrency, formatDate, formatStock } from '@/utils/format'
import {
  CurrencyDollarIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  CubeIcon
} from '@heroicons/vue/24/outline'

const stats = ref({
  today_sales: 0,
  month_sales: 0,
  today_transactions: 0,
  low_stock_products: 0,
  total_products: 0,
  top_products: [],
  recent_sales: []
})

const loadDashboard = async () => {
  try {
    const response = await api.get('/reports/dashboard')
    stats.value = response.data
  } catch (error) {
    console.error('Error loading dashboard:', error)
  }
}

onMounted(() => {
  loadDashboard()
})
</script>
