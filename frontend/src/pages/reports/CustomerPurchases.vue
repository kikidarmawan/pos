<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Laporan Pembelian Customer</h1>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-md">
      <div class="flex">
        <div class="flex-shrink-0">
          <InformationCircleIcon class="h-5 w-5 text-blue-500" />
        </div>
        <div class="ml-3">
          <p class="text-sm text-blue-700">
            <strong>Info:</strong> Data yang ditampilkan pada laporan ini <strong>hanya</strong> mencakup transaksi penjualan yang telah selesai dan dibayar penuh (lunas). Transaksi yang berstatus retur tidak dimasukkan dalam perhitungan ini.
          </p>
        </div>
      </div>
    </div>

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
        <p class="text-sm text-gray-600 mb-1">Total Pelanggan</p>
        <p class="text-2xl font-bold text-primary-600">{{ summary.total_customers || 0 }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Transaksi</p>
        <p class="text-2xl font-bold text-blue-600">{{ summary.total_transactions || 0 }}</p>
      </div>
      <div class="card">
        <p class="text-sm text-gray-600 mb-1">Total Pembelian (Lunas)</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.total_purchases || 0) }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Nama Pelanggan</th>
              <th class="whitespace-nowrap">No HP</th>
              <th class="whitespace-nowrap">Alamat</th>
              <th class="whitespace-nowrap text-right">Jml Transaksi</th>
              <th class="whitespace-nowrap text-right">Jumlah Pembelian</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in data" :key="index">
              <td class="font-medium">{{ row.customer_name }}</td>
              <td>{{ row.customer_phone || '-' }}</td>
              <td class="max-w-xs truncate">{{ row.customer_address || '-' }}</td>
              <td class="text-right">{{ row.total_transactions }}</td>
              <td class="text-right font-medium text-green-600">{{ formatCurrency(row.total_purchases) }}</td>
            </tr>
            <tr v-if="!loading && data.length === 0">
              <td colspan="5" class="text-center text-gray-500 py-8">
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
import { MagnifyingGlassIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'

const toast = useToast()
const loading = ref(false)
const data = ref([])
const summary = ref({ total_customers: 0, total_transactions: 0, total_purchases: 0 })

const datePreset = ref('this_month')
const filters = ref({
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
    filters.value.end_date = formatDate(today) // until today, or could be end of month
  } else if (datePreset.value === 'this_year') {
    const startOfYear = new Date(today.getFullYear(), 0, 1)
    filters.value.start_date = formatDate(startOfYear)
    filters.value.end_date = formatDate(today)
  }
}

const loadReport = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.value.start_date) params.start_date = filters.value.start_date
    if (filters.value.end_date) params.end_date = filters.value.end_date
    const res = await api.get('/reports/customer-purchases', { params })
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
