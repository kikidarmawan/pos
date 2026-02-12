<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Return Pembelian</h1>
      <router-link v-if="hasPermission('create_purchases')" to="/purchase-returns/create" class="btn btn-primary">
        <PlusIcon class="w-5 h-5 mr-2" />
        Buat Retur
      </router-link>
    </div>

    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <input v-model="filters.search" @input="onSearchInput" type="text" placeholder="Cari no. retur atau invoice pembelian..." class="input" />
        <select v-model="filters.warehouse_id" @change="loadReturns(1)" class="input">
          <option value="">Semua Gudang</option>
          <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
        </select>
        <input v-model="filters.start_date" @change="loadReturns(1)" type="date" class="input" placeholder="Dari tanggal" />
        <input v-model="filters.end_date" @change="loadReturns(1)" type="date" class="input" placeholder="Sampai tanggal" />
      </div>
    </div>

    <div class="card overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <p class="mt-2 text-gray-600">Memuat data...</p>
      </div>

      <div v-else-if="!returns.data || returns.data.length === 0" class="p-8 text-center">
        <p class="text-gray-600 mb-4">Belum ada data retur pembelian.</p>
        <router-link v-if="hasPermission('create_purchases')" to="/purchase-returns/create" class="btn btn-primary">
          <PlusIcon class="w-5 h-5 mr-2" />
          Buat Retur Pertama
        </router-link>
      </div>

      <div v-else>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="whitespace-nowrap">No. Retur</th>
                <th class="whitespace-nowrap">Invoice Pembelian</th>
                <th class="whitespace-nowrap">Tanggal Retur</th>
                <th class="whitespace-nowrap">Gudang</th>
                <th class="whitespace-nowrap">Total</th>
                <th class="whitespace-nowrap">Status</th>
                <th class="whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in returns.data" :key="r.id">
                <td class="font-medium">{{ r.return_number }}</td>
                <td>{{ r.purchase?.invoice_number || '-' }}</td>
                <td>{{ formatDate(r.return_date) }}</td>
                <td>{{ r.warehouse?.name || '-' }}</td>
                <td>{{ formatCurrency(r.total) }}</td>
                <td>
                  <span :class="r.status === 'completed' ? 'badge badge-success' : 'badge badge-danger'">
                    {{ r.status === 'completed' ? 'Selesai' : 'Dibatalkan' }}
                  </span>
                </td>
                <td>
                  <button type="button" @click="openDetail(r.id)" class="btn btn-sm btn-outline">Detail</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="returns.last_page > 1" class="px-4 py-3 border-t flex flex-wrap items-center justify-between gap-2">
          <div class="text-sm text-gray-600">
            Menampilkan {{ (returns.current_page - 1) * returns.per_page + 1 }} -
            {{ Math.min(returns.current_page * returns.per_page, returns.total) }} dari {{ returns.total }} data
          </div>
          <div class="flex items-center gap-2">
            <select v-model="filters.per_page" @change="loadReturns(1)" class="input py-1.5 text-sm w-20">
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
            <span class="text-sm text-gray-500">per halaman</span>
            <div class="flex gap-1 ml-2">
              <button type="button" @click="loadReturns(returns.current_page - 1)" :disabled="returns.current_page <= 1"
                class="btn btn-sm btn-outline py-1 px-2">&laquo;</button>
              <button v-for="p in paginationPages" :key="p" type="button"
                @click="p !== '...' && loadReturns(p)"
                :class="['btn btn-sm py-1 px-2', p === returns.current_page ? 'btn-primary' : 'btn-outline']"
                :disabled="p === '...'">{{ p }}</button>
              <button type="button" @click="loadReturns(returns.current_page + 1)"
                :disabled="returns.current_page >= returns.last_page"
                class="btn btn-sm btn-outline py-1 px-2">&raquo;</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="selectedReturn" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="selectedReturn = null">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Detail Retur {{ selectedReturn.return_number }}</h2>
            <button @click="selectedReturn = null" class="text-gray-500 hover:text-gray-700">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>
          <dl class="grid grid-cols-2 gap-2 text-sm mb-4">
            <dt class="text-gray-500">Invoice Pembelian</dt>
            <dd>{{ selectedReturn.purchase?.invoice_number }}</dd>
            <dt class="text-gray-500">Tanggal Retur</dt>
            <dd>{{ formatDate(selectedReturn.return_date) }}</dd>
            <dt class="text-gray-500">Gudang</dt>
            <dd>{{ selectedReturn.warehouse?.name }}</dd>
            <dt class="text-gray-500">Total</dt>
            <dd class="font-medium">{{ formatCurrency(selectedReturn.total) }}</dd>
            <dt class="text-gray-500" v-if="selectedReturn.notes">Catatan</dt>
            <dd v-if="selectedReturn.notes">{{ selectedReturn.notes }}</dd>
          </dl>
          <h3 class="font-semibold mb-2">Item Retur</h3>
          <table class="table text-sm">
            <thead>
              <tr>
                <th>Produk</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in selectedReturn.details" :key="d.id">
                <td>{{ d.product?.name }}</td>
                <td>{{ formatStock(d.quantity) }} {{ d.unit?.name }}</td>
                <td>{{ formatCurrency(d.price) }}</td>
                <td>{{ formatCurrency(d.subtotal) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatDate } from '@/utils/format'
import { formatStock } from '@/utils/format'
import { PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toast = useToast()
const loading = ref(false)
const returns = ref({ data: [] })
const warehouses = ref([])
const selectedReturn = ref(null)

const hasPermission = (permission) => authStore.hasPermission(permission)

const filters = ref({
  search: '',
  warehouse_id: '',
  start_date: '',
  end_date: '',
  per_page: 15
})

let searchTimeout = null
const onSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadReturns(1), 400)
}

const paginationPages = computed(() => {
  const cur = returns.value.current_page || 1
  const last = returns.value.last_page || 1
  if (last <= 7) return [...Array(last)].map((_, i) => i + 1)
  if (cur <= 4) return [1, 2, 3, 4, 5, '...', last]
  if (cur >= last - 3) return [1, '...', last - 4, last - 3, last - 2, last - 1, last]
  return [1, '...', cur - 1, cur, cur + 1, '...', last]
})

const loadReturns = async (page) => {
  loading.value = true
  try {
    const params = { ...filters.value }
    params.page = page ?? returns.value?.current_page ?? 1
    const response = await api.get('/purchase-returns', { params })
    returns.value = response.data
  } catch (error) {
    toast.error('Gagal memuat data retur pembelian')
  } finally {
    loading.value = false
  }
}

const loadWarehouses = async () => {
  try {
    const res = await api.get('/warehouses', { params: { per_page: 100 } })
    warehouses.value = res.data.data ?? res.data
  } catch (e) {
    console.error(e)
  }
}

const openDetail = async (id) => {
  try {
    const res = await api.get(`/purchase-returns/${id}`)
    selectedReturn.value = res.data
  } catch (e) {
    toast.error('Gagal memuat detail retur')
  }
}

onMounted(() => {
  loadReturns()
  loadWarehouses()
})
</script>
