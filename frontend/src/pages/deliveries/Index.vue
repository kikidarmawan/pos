<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Data Pengiriman</h1>
      <button v-if="hasPermission('create_deliveries')" @click="$router.push({ name: 'DeliveriesCreate' })" class="btn btn-primary flex items-center gap-2">
        <PlusIcon class="w-5 h-5" />
        Tambah Pengiriman
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-slate-100 overflow-hidden flex flex-col min-h-[680px]">
      <div class="p-5 bg-slate-50/50 border-b border-slate-100 flex flex-wrap gap-4 items-center justify-between">
        <div class="relative w-96">
          <input v-model="search" type="text" placeholder="Cari No Pengiriman/Sopir/Mobil..." class="input pl-10 bg-white border-slate-200 hover:border-slate-300 focus:border-primary-500 focus:ring-primary-200 transition-all shadow-sm rounded-lg text-sm" />
          <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
        </div>
        <select v-model="statusFilter" class="input w-auto bg-white border-slate-200 hover:border-slate-300 focus:border-primary-500 focus:ring-primary-200 transition-all shadow-sm rounded-lg text-sm">
          <option value="">Semua Status</option>
          <option value="pending">Menunggu (Pending)</option>
          <option value="departing">Berangkat</option>
          <option value="completed">Selesai</option>
          <option value="canceled">Batal</option>
        </select>
      </div>

      <div class="flex-1 overflow-auto">
        <table class="w-full text-sm text-left text-slate-500">
          <thead class="bg-slate-50/70 border-b border-slate-200/80">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">No Pengiriman</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Sopir</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Mobil</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Waktu</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Durasi</th>
              <th v-if="hasPermission('edit_deliveries') || hasPermission('view_deliveries')" class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading">
              <td colspan="7" class="text-center py-8 text-slate-500">
                <div class="flex items-center justify-center gap-2">
                  <div class="spinner w-5 h-5 !border-2"></div>
                  Memuat data...
                </div>
              </td>
            </tr>
            <tr v-else-if="deliveries.data.length === 0">
              <td colspan="7" class="py-44 text-center">
                <div class="flex flex-col items-center justify-center text-center select-none">
                  <div class="w-14 h-14 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center mb-3 border border-slate-200/60 shadow-sm">
                    <TruckIcon class="w-7 h-7 opacity-60" />
                  </div>
                  <h3 class="font-bold text-slate-700 text-sm">Tidak ada data pengiriman</h3>
                  <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">Data rencana pengiriman barang Anda akan tampil di sini setelah Anda menambahkannya.</p>
                </div>
              </td>
            </tr>
            <tr v-else v-for="d in deliveries.data" :key="d.id" class="hover:bg-slate-50/40 transition-colors duration-150">
              <td class="px-6 py-4 align-middle">
                <button
                  v-if="hasPermission('view_deliveries')"
                  @click="viewDetail(d)"
                  type="button"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-blue-50 text-blue-700 font-mono text-sm font-semibold border border-blue-100/80 shadow-sm hover:bg-blue-100 hover:text-blue-800 transition-all duration-150 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-200"
                >
                  {{ d.delivery_number }}
                </button>
                <div v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-blue-50 text-blue-700 font-mono text-sm font-semibold border border-blue-100/80 shadow-sm">
                  {{ d.delivery_number }}
                </div>
                <div v-if="d.user" class="mt-1 text-[10px] text-slate-400">
                  Oleh: <span class="font-medium text-slate-600">{{ d.user.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 align-middle">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200 shadow-sm">
                    <UserIcon class="w-4 h-4" />
                  </div>
                  <div>
                    <div class="font-semibold text-slate-800 text-sm">{{ d.driver?.name }}</div>
                    <div class="text-[10px] text-slate-400">Sopir</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 align-middle">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200 shadow-sm">
                    <TruckIcon class="w-4 h-4" />
                  </div>
                  <div>
                    <div class="font-semibold text-slate-800 text-sm">{{ d.vehicle?.name }}</div>
                    <div class="mt-0.5 inline-flex items-center whitespace-nowrap px-1.5 py-0.5 text-[10px] font-mono font-bold bg-slate-800 text-white rounded border border-slate-900 shadow-sm tracking-wide">
                      {{ d.vehicle?.license_plate }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 align-middle">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold shadow-sm border"
                  :class="{
                    'bg-amber-50 text-amber-700 border-amber-200': d.status === 'pending',
                    'bg-blue-50 text-blue-700 border-blue-200': d.status === 'departing',
                    'bg-emerald-50 text-emerald-700 border-emerald-200': d.status === 'completed',
                    'bg-rose-50 text-rose-700 border-rose-200': d.status === 'canceled'
                  }">
                  <span class="w-1.5 h-1.5 rounded-full"
                    :class="{
                      'bg-amber-500 animate-pulse': d.status === 'pending',
                      'bg-blue-500 animate-pulse': d.status === 'departing',
                      'bg-emerald-500': d.status === 'completed',
                      'bg-rose-500': d.status === 'canceled'
                    }"></span>
                  {{ d.status === 'pending' ? 'PENDING' : d.status === 'departing' ? 'BERANGKAT' : d.status === 'completed' ? 'SELESAI' : 'BATAL' }}
                </span>
              </td>
              <td class="px-6 py-4 align-middle text-xs">
                <div class="flex flex-col gap-1.5">
                  <div v-if="d.departed_at" class="flex items-center gap-1.5">
                    <span class="text-[9px] font-bold uppercase tracking-wider bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded border border-blue-100">Brt</span>
                    <span class="text-slate-600 font-medium">{{ formatDateTime(d.departed_at) }}</span>
                  </div>
                  <div v-if="d.completed_at" class="flex items-center gap-1.5">
                    <span class="text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-600 px-1.5 py-0.5 rounded border border-emerald-100">Sls</span>
                    <span class="text-slate-600 font-medium">{{ formatDateTime(d.completed_at) }}</span>
                  </div>
                  <div v-if="!d.departed_at && !d.completed_at" class="flex items-center gap-1 text-slate-400 italic">
                    <ClockIcon class="w-3.5 h-3.5" /> Belum Mulai
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 align-middle">
                <span v-if="d.status === 'canceled'" class="text-slate-400 font-medium">-</span>
                <span v-else-if="!d.departed_at" class="text-slate-400 font-medium">-</span>
                <div v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold shadow-sm">
                  <ClockIcon class="w-3.5 h-3.5 text-slate-500" />
                  {{ formatDuration(d.departed_at, d.completed_at) }}
                </div>
              </td>
              <td class="px-6 py-4 align-middle text-right space-x-1.5 whitespace-nowrap" v-if="hasPermission('edit_deliveries') || hasPermission('view_deliveries')">
                <button v-if="hasPermission('view_deliveries')" @click="printDelivery(d)" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm focus:ring-2 focus:ring-slate-100">
                  <PrinterIcon class="w-3.5 h-3.5" />
                  Surat Jalan
                </button>
                <button v-if="hasPermission('edit_deliveries') && d.status === 'pending'" @click="confirmAction(d, 'depart')" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm focus:ring-2 focus:ring-blue-100">
                  <PlayIcon class="w-3.5 h-3.5" />
                  Berangkat
                </button>
                <button v-if="hasPermission('edit_deliveries') && d.status === 'departing'" @click="confirmAction(d, 'complete')" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-sm focus:ring-2 focus:ring-emerald-100">
                  <CheckIcon class="w-3.5 h-3.5" />
                  Selesai
                </button>
                <button v-if="hasPermission('edit_deliveries') && d.status !== 'completed' && d.status !== 'canceled'" @click="confirmAction(d, 'cancel')" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors border border-rose-100 focus:ring-2 focus:ring-rose-50">
                  <XMarkIcon class="w-3.5 h-3.5" />
                  Batal
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="p-4 border-t flex items-center justify-between" v-if="deliveries.last_page > 1">
        <span class="text-sm text-gray-600">
          Menampilkan {{ deliveries.from }} - {{ deliveries.to }} dari {{ deliveries.total }} data
        </span>
        <div class="flex gap-2">
          <button class="btn btn-secondary px-3 py-1" :disabled="deliveries.current_page === 1" @click="fetchDeliveries(deliveries.current_page - 1)">
            Sebelumnya
          </button>
          <button class="btn btn-secondary px-3 py-1" :disabled="deliveries.current_page === deliveries.last_page" @click="fetchDeliveries(deliveries.current_page + 1)">
            Selanjutnya
          </button>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <DeliveryDetailModal v-if="showDetailModal" :delivery="selectedDelivery" @close="closeDetail" />

    <!-- Generic Action Modal -->
    <div v-if="showActionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto" @click.self="showActionModal = false">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 m-4 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2" :class="activeActionConfig.barColor"></div>
        <div class="flex flex-col items-center text-center mt-4">
          <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" :class="activeActionConfig.iconBgClass">
            <component :is="activeActionConfig.icon" class="w-8 h-8" :class="activeActionConfig.iconColor" />
          </div>
          <h2 class="text-xl font-bold text-gray-800 mb-2">{{ activeActionConfig.title }}</h2>
          <p class="text-gray-600 mb-6">
            Apakah Anda yakin ingin {{ activeActionConfig.actionText }} pengiriman <strong>{{ selectedDelivery?.delivery_number }}</strong>?
            <span v-if="currentActionType === 'cancel'" class="block mt-2 text-sm">
              Barang yang dibatalkan akan dikembalikan ke daftar antrian pengiriman.
            </span>
          </p>
          <div class="flex gap-3 w-full">
            <button @click="showActionModal = false" class="flex-1 btn btn-secondary py-2.5">Kembali</button>
            <button @click="executeAction" :disabled="processingAction" class="flex-1 btn text-white py-2.5" :class="activeActionConfig.btnColor">
              {{ processingAction ? 'Memproses...' : activeActionConfig.btnText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { PlusIcon, MagnifyingGlassIcon, ExclamationTriangleIcon, PlayIcon, CheckCircleIcon, UserIcon, TruckIcon, ClockIcon, PrinterIcon, EyeIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import debounce from 'lodash/debounce'
import api from '@/utils/axios'
import DeliveryDetailModal from './DeliveryDetailModal.vue'

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const authStore = useAuthStore()
const hasPermission = (perm) => authStore.hasPermission(perm)
const toast = useToast()

const loading = ref(false)
const deliveries = ref({ data: [] })
const search = ref('')
const statusFilter = ref('')

const showDetailModal = ref(false)
const selectedDelivery = ref(null)

const showActionModal = ref(false)
const processingAction = ref(false)
const currentActionType = ref('') // 'depart', 'complete', 'cancel'

const actionConfigs = {
  depart: {
    barColor: 'bg-blue-500',
    iconBgClass: 'bg-blue-100',
    iconColor: 'text-blue-500',
    icon: PlayIcon,
    title: 'Berangkatkan Pengiriman?',
    actionText: 'memberangkatkan',
    btnColor: 'bg-blue-600 hover:bg-blue-700',
    btnText: 'Ya, Berangkat',
    endpoint: 'depart',
    successMsg: 'Pengiriman berangkat'
  },
  complete: {
    barColor: 'bg-green-500',
    iconBgClass: 'bg-green-100',
    iconColor: 'text-green-500',
    icon: CheckCircleIcon,
    title: 'Selesaikan Pengiriman?',
    actionText: 'menyelesaikan',
    btnColor: 'bg-green-600 hover:bg-green-700',
    btnText: 'Ya, Selesai',
    endpoint: 'complete',
    successMsg: 'Pengiriman selesai'
  },
  cancel: {
    barColor: 'bg-red-500',
    iconBgClass: 'bg-red-100',
    iconColor: 'text-red-500',
    icon: ExclamationTriangleIcon,
    title: 'Batalkan Pengiriman?',
    actionText: 'membatalkan',
    btnColor: 'bg-red-600 hover:bg-red-700',
    btnText: 'Ya, Batalkan',
    endpoint: 'cancel',
    successMsg: 'Pengiriman dibatalkan'
  }
}

const activeActionConfig = computed(() => actionConfigs[currentActionType.value] || actionConfigs.depart)

const viewDetail = (d) => {
  selectedDelivery.value = d
  showDetailModal.value = true
}

const closeDetail = () => {
  showDetailModal.value = false
  selectedDelivery.value = null
}

const confirmAction = (d, type) => {
  selectedDelivery.value = d
  currentActionType.value = type
  showActionModal.value = true
}

const executeAction = async () => {
  if (!selectedDelivery.value) return
  
  processingAction.value = true
  const config = activeActionConfig.value
  
  try {
    await api.post(`/deliveries/${selectedDelivery.value.id}/${config.endpoint}`)
    toast.success(config.successMsg)
    showActionModal.value = false
    selectedDelivery.value = null
    fetchDeliveries(deliveries.value.current_page)
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal mengubah status')
  } finally {
    processingAction.value = false
  }
}

const fetchDeliveries = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await api.get('/deliveries', {
      params: {
        page,
        search: search.value,
        status: statusFilter.value
      }
    })
    deliveries.value = data
  } catch (e) {
    toast.error('Gagal mengambil data pengiriman')
  } finally {
    loading.value = false
  }
}

const printDelivery = (d) => {
  window.open(`/deliveries/${d.id}/print`, '_blank')
}

const formatDuration = (start, end) => {
  if (!start) return '-'
  const startTime = new Date(start).getTime()
  const endTime = end ? new Date(end).getTime() : new Date().getTime()
  
  const diffMinutes = Math.floor((endTime - startTime) / (1000 * 60))
  if (diffMinutes < 60) return `${diffMinutes} mnt`
  
  const hours = Math.floor(diffMinutes / 60)
  const mins = diffMinutes % 60
  return `${hours} j ${mins} mnt`
}

const debouncedSearch = debounce(() => fetchDeliveries(1), 500)
watch([search, statusFilter], () => debouncedSearch())

onMounted(() => {
  fetchDeliveries()
})
</script>
