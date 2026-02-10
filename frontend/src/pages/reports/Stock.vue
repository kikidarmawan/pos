<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Laporan Stok</h1>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <select v-model="filters.warehouse_id" class="input">
          <option value="">Semua Gudang</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
            {{ wh.name }}
          </option>
        </select>
        <select v-model="filters.category_id" class="input">
          <option value="">Semua Kategori</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>
        <label class="flex items-center gap-2">
          <input v-model="filters.low_stock" type="checkbox" class="rounded" />
          <span>Stok Rendah Saja</span>
        </label>
        <div class="flex gap-2">
          <button @click="loadReport" :disabled="loading" class="btn btn-primary flex-1">
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Produk</p>
        <p class="text-2xl font-bold text-primary-600">{{ summary.total_products }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Nilai Stok</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.total_stock_value) }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Produk Stok Rendah</p>
        <p class="text-2xl font-bold text-red-600">{{ summary.low_stock_products }}</p>
      </div>
    </div>

    <!-- Products Table -->
    <div class="card overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500">Memuat...</div>
      <div v-else class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Kode</th>
              <th class="whitespace-nowrap">Nama Produk</th>
              <th class="whitespace-nowrap">Kategori</th>
              <th class="whitespace-nowrap">Stok</th>
              <th class="whitespace-nowrap">Min. Stok</th>
              <th class="whitespace-nowrap">Nilai Stok</th>
              <th class="whitespace-nowrap">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products" :key="product.id">
              <td class="font-medium">{{ product.code }}</td>
              <td>{{ product.name }}</td>
              <td>{{ product.category?.name }}</td>
              <td>
                <span :class="(product.total_stock || 0) < (product.minimum_stock || 0) ? 'text-red-600 font-bold' : ''">
                  {{ formatStock(product.total_stock ?? 0) }} {{ product.base_unit?.name || product.baseUnit?.name || '-' }}
                </span>
              </td>
              <td>{{ formatStock(product.minimum_stock ?? 0) }} {{ product.base_unit?.name || product.baseUnit?.name || '' }}</td>
              <td>{{ formatCurrency((product.total_stock || 0) * product.base_price) }}</td>
              <td>
                <span :class="(product.total_stock || 0) < product.minimum_stock
                  ? 'badge badge-danger'
                  : 'badge badge-success'
                  ">
                  {{ (product.total_stock || 0) < product.minimum_stock ? 'Rendah' : 'Normal' }} </span>
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
import { formatCurrency, formatStock } from '@/utils/format'
import { MagnifyingGlassIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toast = useToast()

const warehouses = ref([])
const categories = ref([])
const products = ref([])
const summary = ref({
  total_products: 0,
  total_stock_value: 0,
  low_stock_products: 0
})

const filters = ref({
  warehouse_id: '',
  category_id: '',
  low_stock: false
})

const hasPermission = (permission) => authStore.hasPermission(permission)

const loading = ref(false)
const loadReport = async () => {
  loading.value = true
  try {
    const params = { ...filters.value }
    if (!params.low_stock) delete params.low_stock
    const response = await api.get('/reports/stock', { params })
    products.value = response.data.products || []
    summary.value = response.data.summary || { total_products: 0, total_stock_value: 0, low_stock_products: 0 }
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal memuat laporan')
    products.value = []
  } finally {
    loading.value = false
  }
}

const exportReport = async () => {
  try {
    const response = await api.get('/reports/export/stock', {
      params: filters.value,
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `laporan-stok-${new Date().toISOString().split('T')[0]}.xlsx`)
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

const loadCategories = async () => {
  try {
    const response = await api.get('/categories', { params: { per_page: 100 } })
    categories.value = response.data.data
  } catch (error) {
    console.error('Error loading categories:', error)
  }
}

onMounted(() => {
  loadWarehouses()
  loadCategories()
  loadReport()
})
</script>
