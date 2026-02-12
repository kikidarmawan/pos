<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <p class="text-gray-600">Welcome, {{ user?.name || 'User' }}</p>
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
      </div>

      <!-- Filter periode (seperti di gambar) -->
      <div class="flex flex-wrap gap-2">
        <button
          v-for="opt in periodOptions"
          :key="opt.value"
          type="button"
          @click="period = opt.value; loadDashboard()"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
            period === opt.value
              ? 'bg-primary-600 text-white'
              : 'bg-primary-100 text-primary-700 hover:bg-primary-200'
          ]"
        >
          {{ opt.label }}
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <div class="card bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-white/90 uppercase tracking-wide">Total Penjualan</p>
            <p class="text-lg font-bold mt-1">{{ formatCurrency(stats.period_sales) }}</p>
            <p class="text-xs text-white/75 mt-1">{{ stats.period_transactions }} transaksi</p>
          </div>
          <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center shrink-0">
            <CurrencyDollarIcon class="w-6 h-6 text-white" />
          </div>
        </div>
      </div>

      <div class="card bg-gradient-to-br from-green-500 to-green-600 text-white shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-white/90 uppercase tracking-wide">Total Pembelian</p>
            <p class="text-lg font-bold mt-1">{{ formatCurrency(stats.period_purchases) }}</p>
          </div>
          <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center shrink-0">
            <ShoppingCartIcon class="w-6 h-6 text-white" />
          </div>
        </div>
      </div>

      <div class="card bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-white/90 uppercase tracking-wide">Total Retur Penjualan</p>
            <p class="text-lg font-bold mt-1">{{ formatCurrency(stats.period_sale_returns) }}</p>
          </div>
          <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center shrink-0">
            <ArrowPathIcon class="w-6 h-6 text-white" />
          </div>
        </div>
      </div>

      <div class="card bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-white/90 uppercase tracking-wide">Total Retur Pembelian</p>
            <p class="text-lg font-bold mt-1">{{ formatCurrency(stats.period_purchase_returns) }}</p>
          </div>
          <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center shrink-0">
            <DocumentDuplicateIcon class="w-6 h-6 text-white" />
          </div>
        </div>
      </div>
    </div>

    <!-- Grafik Line 30 Hari: Penjualan, Pembelian, Retur Penjualan, Retur Pembelian -->
    <div class="card mb-6">
      <h2 class="text-xl font-bold text-gray-900 mb-4">Transaksi 30 Hari Terakhir</h2>
      <div class="h-80">
        <Line v-if="chartData" :data="chartData" :options="chartOptions" />
        <div v-else class="flex items-center justify-center h-full text-gray-500">
          Memuat grafik...
        </div>
      </div>
    </div>

    <!-- Top Products & Recent Sales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Produk Terlaris (Periode)</h2>
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

    <!-- Stok Produk Rendah -->
    <div class="card mt-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-900">Stok Produk Rendah</h2>
        <router-link v-if="(stats.low_stock_products_list?.length || 0) > 0" to="/stocks" class="text-sm text-primary-600 hover:underline">
          Lihat Penyesuaian Stok
        </router-link>
      </div>
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Produk</th>
              <th class="whitespace-nowrap text-right">Stok Saat Ini</th>
              <th class="whitespace-nowrap text-right">Stok Minimum</th>
              <th class="whitespace-nowrap">Satuan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in (stats.low_stock_products_list || [])" :key="p.id">
              <td class="font-medium">{{ p.name }}</td>
              <td class="text-right text-amber-600">{{ formatStock(p.current_stock) }}</td>
              <td class="text-right">{{ formatStock(p.minimum_stock) }}</td>
              <td>{{ p.unit_name }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!stats.low_stock_products_list?.length" class="text-center text-gray-500 py-8">
        Tidak ada produk dengan stok di bawah minimum
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/utils/axios'
import { formatCurrency, formatDate, formatStock } from '@/utils/format'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import { Line } from 'vue-chartjs'
import {
  CurrencyDollarIcon,
  ShoppingCartIcon,
  ArrowPathIcon,
  DocumentDuplicateIcon
} from '@heroicons/vue/24/outline'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const authStore = useAuthStore()
const user = computed(() => authStore.user)

const periodOptions = [
  { value: 'today', label: 'Hari Ini' },
  { value: 'week', label: 'Minggu Ini' },
  { value: 'month', label: 'Bulan Ini' },
  { value: 'financial_year', label: 'Tahun Fiskal' },
]

const period = ref('today')
const stats = ref({
  period_sales: 0,
  period_purchases: 0,
  period_sale_returns: 0,
  period_purchase_returns: 0,
  period_transactions: 0,
  top_products: [],
  recent_sales: [],
  transactions_chart_30_days: [],
  low_stock_products_list: [],
})

const chartData = computed(() => {
  const rows = stats.value.transactions_chart_30_days || []
  if (!rows.length) return null
  return {
    labels: rows.map((r) => {
      const d = new Date(r.date)
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
    }),
    datasets: [
      {
        label: 'Penjualan',
        data: rows.map((r) => r.sales_total),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.3,
        borderWidth: 2,
        pointRadius: 2,
      },
      {
        label: 'Pembelian',
        data: rows.map((r) => r.purchases_total),
        borderColor: 'rgb(34, 197, 94)',
        backgroundColor: 'rgba(34, 197, 94, 0.1)',
        fill: true,
        tension: 0.3,
        borderWidth: 2,
        pointRadius: 2,
      },
      {
        label: 'Retur Penjualan',
        data: rows.map((r) => r.sale_returns_total),
        borderColor: 'rgb(245, 158, 11)',
        backgroundColor: 'rgba(245, 158, 11, 0.1)',
        fill: true,
        tension: 0.3,
        borderWidth: 2,
        pointRadius: 2,
      },
      {
        label: 'Retur Pembelian',
        data: rows.map((r) => r.purchase_returns_total),
        borderColor: 'rgb(168, 85, 247)',
        backgroundColor: 'rgba(168, 85, 247, 0.1)',
        fill: true,
        tension: 0.3,
        borderWidth: 2,
        pointRadius: 2,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: {
      display: true,
      position: 'top',
    },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${ctx.dataset.label}: ${formatCurrency(ctx.parsed.y)}`,
      },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value) => value >= 1000000 ? (value / 1000000) + 'jt' : value >= 1000 ? (value / 1000) + 'k' : value,
      },
    },
  },
}

const loadDashboard = async () => {
  try {
    const response = await api.get('/reports/dashboard', {
      params: { period: period.value },
    })
    stats.value = response.data
  } catch (error) {
    console.error('Error loading dashboard:', error)
  }
}

onMounted(() => {
  loadDashboard()
})
</script>
