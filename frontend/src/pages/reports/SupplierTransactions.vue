<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Laporan Transaksi Supplier</h1>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="label">Pilih Rentang Waktu</label>
          <select v-model="datePreset" @change="applyDatePreset" class="input">
            <option value="custom">Custom Range</option>
            <option value="today">Hari Ini</option>
            <option value="this_week">Minggu Ini</option>
            <option value="this_month">Bulan Ini</option>
            <option value="this_year">Tahun Ini</option>
          </select>
        </div>
        <div>
          <label class="label">Tanggal Mulai</label>
          <input v-model="filters.start_date" type="date" class="input" :disabled="datePreset !== 'custom'" />
        </div>
        <div>
          <label class="label">Tanggal Akhir</label>
          <input v-model="filters.end_date" type="date" class="input" :disabled="datePreset !== 'custom'" />
        </div>
        <div class="flex items-end">
          <button @click="loadReport" class="btn btn-primary w-full">
            <MagnifyingGlassIcon class="w-5 h-5 mr-2 inline" />
            Tampilkan
          </button>
        </div>
      </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Jumlah Supplier</p>
        <p class="text-2xl font-bold text-primary-600">{{ summary.total_rows }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Transaksi</p>
        <p class="text-2xl font-bold text-blue-600">{{ summary.total_transactions || 0 }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Jumlah Belanja</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.total_belanja) }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">Rincian Transaksi</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
          <thead class="bg-gray-50 text-gray-600 text-xs uppercase sticky top-0 shadow-sm z-10">
            <tr>
              <th class="px-4 py-2.5 border-b font-semibold whitespace-nowrap">Nama</th>
              <th class="px-4 py-2.5 border-b font-semibold whitespace-nowrap">Telepon</th>
              <th class="px-4 py-2.5 border-b font-semibold whitespace-nowrap">Email</th>
              <th class="px-4 py-2.5 border-b font-semibold whitespace-nowrap">Alamat</th>
              <th class="px-4 py-2.5 border-b font-semibold whitespace-nowrap text-center">Jumlah Transaksi</th>
              <th class="px-4 py-2.5 border-b font-semibold whitespace-nowrap text-right">Total Jumlah Belanja</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in data" :key="row.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-2.5 font-medium">{{ row.name }}</td>
              <td class="px-4 py-2.5">{{ row.phone || '-' }}</td>
              <td class="px-4 py-2.5">{{ row.email || '-' }}</td>
              <td class="px-4 py-2.5 max-w-xs truncate">{{ row.address || '-' }}</td>
              <td class="px-4 py-2.5 text-center font-medium">{{ row.total_transactions || 0 }}</td>
              <td class="px-4 py-2.5 text-right font-medium text-green-600">{{ formatCurrency(row.total_belanja) }}</td>
            </tr>
            <tr v-if="!loading && data.length === 0">
              <td colspan="6" class="px-4 py-8 text-center text-gray-500">
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
const summary = ref({ total_rows: 0, total_belanja: 0, total_transactions: 0 })

const datePreset = ref('this_month')
const filters = ref({
  type: 'supplier',
  start_date: '',
  end_date: ''
})

const getStartOfWeek = () => {
  const d = new Date()
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1) // Adjust for Monday start
  return new Date(d.setDate(diff))
}

const applyDatePreset = () => {
  const today = new Date()
  const formatDate = (date) => {
    const d = new Date(date)
    let month = '' + (d.getMonth() + 1)
    let day = '' + d.getDate()
    const year = d.getFullYear()

    if (month.length < 2) month = '0' + month
    if (day.length < 2) day = '0' + day

    return [year, month, day].join('-')
  }

  if (datePreset.value === 'today') {
    filters.value.start_date = formatDate(today)
    filters.value.end_date = formatDate(today)
  } else if (datePreset.value === 'this_week') {
    const startOfWeek = getStartOfWeek()
    filters.value.start_date = formatDate(startOfWeek)
    filters.value.end_date = formatDate(today)
  } else if (datePreset.value === 'this_month') {
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)
    filters.value.start_date = formatDate(startOfMonth)
    filters.value.end_date = formatDate(today)
  } else if (datePreset.value === 'this_year') {
    const startOfYear = new Date(today.getFullYear(), 0, 1)
    filters.value.start_date = formatDate(startOfYear)
    filters.value.end_date = formatDate(today)
  }
}

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

onMounted(() => {
  applyDatePreset()
  loadReport()
})
</script>
