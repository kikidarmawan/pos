<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Laporan Penyesuaian Stok</h1>

    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="label">Tanggal Mulai</label>
          <input v-model="filters.start_date" type="date" class="input" />
        </div>
        <div>
          <label class="label">Tanggal Akhir</label>
          <input v-model="filters.end_date" type="date" class="input" />
        </div>
        <div>
          <label class="label">Tipe</label>
          <select v-model="filters.type" class="input">
            <option value="">Semua</option>
            <option value="in">Masuk</option>
            <option value="out">Keluar</option>
            <option value="adjustment">Penyesuaian</option>
          </select>
        </div>
        <div class="flex items-end">
          <button @click="loadReport" class="btn btn-primary w-full">
            <MagnifyingGlassIcon class="w-5 h-5 mr-2" />
            Tampilkan
          </button>
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
              <th class="whitespace-nowrap">Tanggal</th>
              <th class="whitespace-nowrap">Produk</th>
              <th class="whitespace-nowrap">Gudang</th>
              <th class="whitespace-nowrap">Tipe</th>
              <th class="whitespace-nowrap">Qty</th>
              <th class="whitespace-nowrap">Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in movements.data" :key="m.id">
              <td>{{ formatDate(m.created_at) }}</td>
              <td>{{ m.product?.name || '-' }}</td>
              <td>{{ m.warehouse?.name || '-' }}</td>
              <td>
                <span :class="{
                  'badge badge-success': m.type === 'in',
                  'badge badge-danger': m.type === 'out',
                  'badge badge-info': m.type === 'adjustment'
                }">
                  {{ m.type === 'in' ? 'Masuk' : m.type === 'out' ? 'Keluar' : 'Penyesuaian' }}
                </span>
              </td>
              <td>{{ m.quantity > 0 ? '+' : '' }}{{ m.quantity }}</td>
              <td>{{ m.notes || '-' }}</td>
            </tr>
            <tr v-if="!movements.data?.length">
              <td colspan="6" class="text-center py-8 text-gray-500">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="movements.last_page > 1" class="px-4 py-3 border-t flex justify-end gap-2">
        <button type="button" @click="loadReport(movements.current_page - 1)" :disabled="movements.current_page <= 1"
          class="btn btn-sm btn-outline">&laquo;</button>
        <span class="py-2 text-sm">Halaman {{ movements.current_page }} / {{ movements.last_page }}</span>
        <button type="button" @click="loadReport(movements.current_page + 1)"
          :disabled="movements.current_page >= movements.last_page" class="btn btn-sm btn-outline">&raquo;</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatDate } from '@/utils/format'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const toast = useToast()
const loading = ref(false)
const movements = ref({ data: [] })

const filters = reactive({
  start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
  end_date: new Date().toISOString().slice(0, 10),
  type: '',
})

const loadReport = async (page = 1) => {
  loading.value = true
  try {
    const res = await api.get('/reports/stock-movements', {
      params: { ...filters, page, per_page: 20 },
    })
    movements.value = res.data
  } catch (e) {
    toast.error('Gagal memuat laporan')
  } finally {
    loading.value = false
  }
}

onMounted(() => loadReport())
</script>
