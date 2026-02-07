<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Produk Terlaris</h1>

    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="label">Bulan</label>
          <select v-model="filters.month" @change="loadReport" class="input">
            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
        </div>
        <div>
          <label class="label">Tahun</label>
          <select v-model="filters.year" @change="loadReport" class="input">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div>
          <label class="label">Limit</label>
          <select v-model="filters.limit" @change="loadReport" class="input">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="20">20</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">#</th>
              <th class="whitespace-nowrap">Produk</th>
              <th class="whitespace-nowrap">Qty Terjual</th>
              <th class="whitespace-nowrap">Total Pendapatan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(p, i) in topProducts" :key="p.id">
              <td class="font-medium">{{ i + 1 }}</td>
              <td>{{ p.name }}</td>
              <td>{{ formatStock(p.total_sold) }}</td>
              <td>{{ formatCurrency(p.total_revenue) }}</td>
            </tr>
            <tr v-if="!topProducts.length">
              <td colspan="4" class="text-center py-8 text-gray-500">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatStock } from '@/utils/format'

const toast = useToast()
const loading = ref(false)
const topProducts = ref([])
const filters = reactive({
  month: String(new Date().getMonth() + 1).padStart(2, '0'),
  year: new Date().getFullYear(),
  limit: 10,
})

const months = computed(() =>
  [...Array(12)].map((_, i) => ({
    value: String(i + 1).padStart(2, '0'),
    label: new Date(2000, i).toLocaleString('id-ID', { month: 'long' }),
  }))
)
const years = computed(() => {
  const y = new Date().getFullYear()
  return [...Array(5)].map((_, i) => y - i)
})

const loadReport = async () => {
  loading.value = true
  try {
    const res = await api.get('/reports/dashboard')
    topProducts.value = (res.data.top_products || []).slice(0, filters.limit)
  } catch (e) {
    toast.error('Gagal memuat laporan')
  } finally {
    loading.value = false
  }
}

onMounted(loadReport)
</script>
