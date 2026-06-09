<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full p-6 my-8">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Detail Pengiriman {{ delivery?.delivery_number }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div v-if="loading" class="text-center py-10">
        <p class="text-gray-500">Memuat detail...</p>
      </div>

      <div v-else-if="detail">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Informasi Umum</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-gray-600">Sopir:</span> <span class="font-medium">{{ detail.driver?.name }}</span></div>
              <div class="flex justify-between"><span class="text-gray-600">Kendaraan:</span> <span class="font-medium">{{ detail.vehicle?.name }} ({{ detail.vehicle?.license_plate }})</span></div>
              <div class="flex justify-between"><span class="text-gray-600">Status:</span> 
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                  :class="{
                    'bg-yellow-100 text-yellow-800': detail.status === 'pending',
                    'bg-blue-100 text-blue-800': detail.status === 'departing',
                    'bg-green-100 text-green-800': detail.status === 'completed',
                    'bg-red-100 text-red-800': detail.status === 'canceled'
                  }">
                  {{ detail.status.toUpperCase() }}
                </span>
              </div>
              <div v-if="detail.notes" class="flex justify-between flex-col mt-2">
                <span class="text-gray-600">Catatan:</span>
                <p class="font-medium bg-white p-2 border rounded mt-1 text-gray-800">{{ detail.notes }}</p>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Waktu</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-gray-600">Berangkat:</span> <span class="font-medium">{{ detail.departed_at ? new Date(detail.departed_at).toLocaleString('id-ID') : '-' }}</span></div>
              <div class="flex justify-between"><span class="text-gray-600">Selesai:</span> <span class="font-medium">{{ detail.completed_at ? new Date(detail.completed_at).toLocaleString('id-ID') : '-' }}</span></div>
              <div class="flex justify-between"><span class="text-gray-600">Durasi:</span> <span class="font-medium text-blue-600">{{ duration }}</span></div>
            </div>
          </div>
        </div>

        <h3 class="text-lg font-bold mb-3">Daftar Barang Dikirim</h3>
        
        <div v-if="!groupedItems || groupedItems.length === 0" class="border rounded-lg text-center py-8 text-gray-500">
          Tidak ada item pengiriman
        </div>
        
        <div v-else class="space-y-6">
          <div v-for="(group, index) in groupedItems" :key="index" class="border rounded-lg overflow-hidden shadow-sm">
            <div class="bg-blue-50 px-4 py-3 border-b border-blue-100">
              <h4 class="font-bold text-blue-900 text-base">{{ group.customerName }}</h4>
              <div class="flex items-center gap-4 text-sm text-blue-700 mt-1">
                <div v-if="group.customerPhone" class="flex items-center gap-1">
                  <PhoneIcon class="w-4 h-4" />
                  <span>{{ group.customerPhone }}</span>
                </div>
                <div v-if="group.customerAddress" class="flex items-center gap-1">
                  <MapPinIcon class="w-4 h-4" />
                  <span>{{ group.customerAddress }}</span>
                </div>
              </div>
            </div>
            <div class="overflow-x-auto bg-white">
              <table class="table mb-0">
                <thead class="bg-gray-50">
                  <tr>
                    <th>No Nota</th>
                    <th>Produk</th>
                    <th class="text-right">Kuantitas</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in group.items" :key="item.id">
                    <td class="font-medium">{{ item.sale_detail?.sale?.invoice_number }}</td>
                    <td>
                      {{ item.sale_detail?.product?.name }} 
                      <span class="text-xs text-gray-500">({{ item.sale_detail?.unit?.name }})</span>
                    </td>
                    <td class="text-right font-bold text-blue-600">{{ Number(item.quantity) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <div class="flex justify-end pt-4 mt-6 gap-3">
          <button type="button" @click="$emit('close')" class="btn btn-secondary px-6">Tutup</button>
          <button type="button" @click="printDelivery" class="btn bg-gray-600 text-white hover:bg-gray-700 px-6">Cetak Surat Jalan</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { XMarkIcon, PhoneIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import api from '@/utils/axios'

const props = defineProps({
  delivery: { type: Object, required: true }
})

const emit = defineEmits(['close'])
const router = useRouter()

const detail = ref(null)
const loading = ref(false)

const fetchDetail = async () => {
  if (!props.delivery?.id) return
  loading.value = true
  try {
    const { data } = await api.get(`/deliveries/${props.delivery.id}`)
    detail.value = data
  } catch (e) {
    //
  } finally {
    loading.value = false
  }
}

watch(() => props.delivery, () => {
  fetchDetail()
}, { immediate: true })

const printDelivery = () => {
  if (detail.value?.id) {
    window.open(`/deliveries/${detail.value.id}/print`, '_blank')
  }
}

const duration = computed(() => {
  if (detail.value?.status === 'canceled') return '-'
  if (!detail.value || (!detail.value.departed_at && !detail.value.completed_at)) return '-'
  const start = new Date(detail.value.departed_at).getTime()
  const end = detail.value.completed_at ? new Date(detail.value.completed_at).getTime() : new Date().getTime()
  
  const diffMinutes = Math.floor((end - start) / (1000 * 60))
  if (diffMinutes < 60) return `${diffMinutes} mnt`
  
  const hours = Math.floor(diffMinutes / 60)
  const mins = diffMinutes % 60
  return `${hours} j ${mins} mnt`
})

const groupedItems = computed(() => {
  if (!detail.value?.items) return []
  
  const groups = {}
  
  detail.value.items.forEach(item => {
    const sale = item.sale_detail?.sale || {}
    const customerKey = sale.customer_name || 'Pelanggan Umum'
    
    if (!groups[customerKey]) {
      groups[customerKey] = {
        customerName: customerKey,
        customerPhone: sale.customer_phone || '',
        customerAddress: sale.customer_address || '',
        items: []
      }
    }
    
    // update phone/address if empty but available in another sale
    if (!groups[customerKey].customerPhone && sale.customer_phone) {
      groups[customerKey].customerPhone = sale.customer_phone
    }
    if (!groups[customerKey].customerAddress && sale.customer_address) {
      groups[customerKey].customerAddress = sale.customer_address
    }
    
    groups[customerKey].items.push(item)
  })
  
  return Object.values(groups)
})
</script>
