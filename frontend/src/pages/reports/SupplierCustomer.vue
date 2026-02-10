<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Laporan Supplier & Pelanggan</h1>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
          <label class="label">Filter Data</label>
          <select v-model="filters.type" @change="loadReport" class="input">
            <option value="customer">Pelanggan</option>
            <option value="supplier">Supplier</option>
          </select>
        </div>
        <div>
          <label class="label">Tanggal Mulai</label>
          <input v-model="filters.start_date" type="date" class="input" />
        </div>
        <div>
          <label class="label">Tanggal Akhir</label>
          <input v-model="filters.end_date" type="date" class="input" />
        </div>
        <div class="flex items-end">
          <button @click="loadReport" class="btn btn-primary">
            <MagnifyingGlassIcon class="w-5 h-5 mr-2 inline" />
            Tampilkan
          </button>
        </div>
      </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Jumlah {{ filters.type === 'customer' ? 'Pelanggan' : 'Supplier' }}</p>
        <p class="text-2xl font-bold text-primary-600">{{ summary.total_rows }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Jumlah Belanja</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.total_belanja) }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Nama</th>
              <th class="whitespace-nowrap">Telepon</th>
              <th v-if="filters.type === 'supplier'" class="whitespace-nowrap">Email</th>
              <th class="whitespace-nowrap">Alamat</th>
              <th class="whitespace-nowrap text-right">Total Jumlah Belanja</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in data" :key="row.id">
              <td class="font-medium">{{ row.name }}</td>
              <td>{{ row.phone || '-' }}</td>
              <td v-if="filters.type === 'supplier'">{{ row.email || '-' }}</td>
              <td class="max-w-xs truncate">{{ row.address || '-' }}</td>
              <td class="text-right font-medium text-green-600">{{ formatCurrency(row.total_belanja) }}</td>
            </tr>
            <tr v-if="!loading && data.length === 0">
              <td :colspan="filters.type === 'supplier' ? 5 : 4" class="text-center text-gray-500 py-8">
                Tidak ada data
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
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency } from '@/utils/format'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const toast = useToast()
const loading = ref(false)
const data = ref([])
const summary = ref({ total_rows: 0, total_belanja: 0 })

const filters = ref({
  type: 'customer',
  start_date: '',
  end_date: ''
})

const loadReport = async () => {
  loading.value = true
  try {
    const params = { type: filters.value.type }
    if (filters.value.start_date) params.start_date = filters.value.start_date
    if (filters.value.end_date) params.end_date = filters.value.end_date
    const res = await api.get('/reports/supplier-customer', { params })
    data.value = res.data.data
    summary.value = res.data.summary
  } catch (e) {
    toast.error('Gagal memuat laporan')
  } finally {
    loading.value = false
  }
}

onMounted(loadReport)
</script>
