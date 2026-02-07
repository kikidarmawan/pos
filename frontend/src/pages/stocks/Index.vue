<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Manajemen Stok</h1>
      <button @click="openStockInputModal()" class="btn btn-primary">
        <PlusIcon class="w-5 h-5 mr-2" />
        Input Stok Manual
      </button>
    </div>

    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadStocks" type="text" placeholder="Cari produk..." class="input" />
        <select v-model="filters.warehouse_id" @change="loadStocks" class="input">
          <option value="">Semua Gudang</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
            {{ wh.name }}
          </option>
        </select>
      </div>
    </div>

    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Produk</th>
              <th class="whitespace-nowrap">Gudang</th>
              <th class="whitespace-nowrap">Rak</th>
              <th class="whitespace-nowrap">Stok</th>
              <th class="whitespace-nowrap">Min. Stok</th>
              <th class="whitespace-nowrap">Status</th>
              <th class="whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="stock in stocks.data" :key="stock.id">
              <td>
                <div class="font-medium">{{ stock.product?.name }}</div>
                <div class="text-xs text-gray-500">{{ stock.product?.code }}</div>
              </td>
              <td>{{ stock.warehouse?.name }}</td>
              <td>{{ stock.rack?.name || '-' }}</td>
              <td>
                <span :class="stock.quantity < stock.product?.minimum_stock ? 'text-red-600 font-bold' : ''">
                  {{ formatStock(stock.quantity) }} {{ stock.product?.base_unit?.name || stock.product?.baseUnit?.name || '-' }}
                </span>
              </td>
              <td>{{ formatStock(stock.product?.minimum_stock ?? 0) }} {{ stock.product?.base_unit?.name || stock.product?.baseUnit?.name || '' }}</td>
              <td>
                <span :class="stock.quantity < stock.product?.minimum_stock
                  ? 'badge badge-danger'
                  : 'badge badge-success'
                  ">
                  {{ stock.quantity < stock.product?.minimum_stock ? 'Rendah' : 'Normal' }} </span>
              </td>
              <td>
                <button @click="openStockInputModal(stock)" class="text-green-600 hover:text-green-800"
                  title="Input Stok">
                  <PlusCircleIcon class="w-5 h-5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <StockInputModal v-if="showStockInputModal" :product="selectedStockProduct"
      :warehouse-id="selectedWarehouseId" @close="closeStockInputModal" @saved="handleStockSaved" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { PlusIcon, PlusCircleIcon } from '@heroicons/vue/24/outline'
import api from '@/utils/axios'
import { formatStock } from '@/utils/format'
import StockInputModal from '@/components/StockInputModal.vue'

const toast = useToast()
const stocks = ref({ data: [] })
const warehouses = ref([])

const showStockInputModal = ref(false)
const selectedStockProduct = ref(null)
const selectedWarehouseId = ref(null)

const filters = ref({
  search: '',
  warehouse_id: ''
})

const openStockInputModal = (stock = null) => {
  if (stock) {
    selectedStockProduct.value = stock.product
    selectedWarehouseId.value = stock.warehouse_id
  } else {
    selectedStockProduct.value = null
    selectedWarehouseId.value = null
  }
  showStockInputModal.value = true
}

const closeStockInputModal = () => {
  showStockInputModal.value = false
  selectedStockProduct.value = null
  selectedWarehouseId.value = null
}

const handleStockSaved = () => {
  toast.success('Stok berhasil disesuaikan')
  loadStocks()
}

const loadStocks = async () => {
  try {
    const response = await api.get('/stocks', { params: filters.value })
    stocks.value = response.data
  } catch (error) {
    toast.error('Gagal memuat stok')
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
  loadStocks()
  loadWarehouses()
})
</script>
