<template>
  <div class="print-container p-8 max-w-4xl mx-auto bg-white min-h-screen text-slate-800">
    <div v-if="loading" class="flex flex-col items-center justify-center py-12 gap-2 text-slate-500">
      <div class="spinner w-8 h-8"></div>
      <span>Memuat data surat jalan...</span>
    </div>
    <div v-else-if="delivery">
      <!-- Document Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pb-6 mb-6 border-b border-slate-200">
        <!-- Store info -->
        <div class="flex items-start gap-3">
          <div class="w-12 h-12 rounded-xl bg-slate-900 text-white print:bg-white print:text-slate-900 print:border print:border-slate-300 flex items-center justify-center font-bold text-xl shadow-md print:shadow-none">
            {{ store?.name?.charAt(0) || 'P' }}
          </div>
          <div>
            <h3 class="font-bold text-lg text-slate-800 leading-tight">{{ store?.name || 'Toko Retail' }}</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-xs">{{ store?.address }}</p>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Telp: {{ store?.phone }}</p>
          </div>
        </div>
        
        <!-- Document meta -->
        <div class="sm:text-right">
          <h1 class="text-2xl font-extrabold uppercase tracking-wider text-slate-900">SURAT JALAN</h1>
          <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 rounded-lg text-slate-700 font-mono text-sm font-semibold border border-slate-200/60 shadow-sm">
            {{ delivery.delivery_number }}
          </div>
          <div class="text-xs text-slate-400 mt-2 flex flex-col sm:items-end gap-1">
            <p><strong>Tanggal:</strong> {{ new Date(delivery.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            <p v-if="delivery.departed_at"><strong>Waktu Berangkat:</strong> {{ new Date(delivery.departed_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }} WIB</p>
          </div>
        </div>
      </div>

      <!-- Info Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Delivery info -->
        <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 shadow-sm">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3.5 border-b border-slate-200/60 pb-1.5">Informasi Pengiriman</h3>
          <div class="space-y-2.5 text-sm text-slate-600">
            <div class="flex items-start">
              <span class="w-24 font-medium text-slate-400 text-xs uppercase pt-0.5">Sopir</span>
              <div class="flex-1 font-semibold text-slate-800">
                {{ delivery.driver?.name }} 
                <span class="block text-xs font-normal text-slate-400 mt-0.5">KTP: {{ delivery.driver?.no_ktp || '-' }}</span>
              </div>
            </div>
            <div class="flex items-center">
              <span class="w-24 font-medium text-slate-400 text-xs uppercase">Kendaraan</span>
              <div class="flex-1 flex items-center gap-2">
                <span class="font-semibold text-slate-800 text-sm">{{ delivery.vehicle?.name }}</span>
                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono font-bold bg-slate-800 text-white print:bg-white print:text-slate-800 print:border-slate-300 rounded border border-slate-900 shadow-sm print:shadow-none tracking-wide">
                  {{ delivery.vehicle?.license_plate }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes info -->
        <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 shadow-sm flex flex-col">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3.5 border-b border-slate-200/60 pb-1.5">Catatan Pengiriman</h3>
          <p class="text-sm text-slate-600 italic flex-1">
            {{ delivery.notes || 'Tidak ada catatan tambahan untuk pengiriman ini.' }}
          </p>
        </div>
      </div>

      <!-- Grouped items by Customer/Sale -->
      <div v-for="(group, index) in groupedItems" :key="index" class="mb-8 border border-slate-200 rounded-xl overflow-hidden shadow-sm print:shadow-none">
        <div class="bg-slate-900 px-5 py-3.5 text-white print:bg-slate-50 print:text-slate-900 print:border-b print:border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400 print:text-slate-500">Penerima</span>
            <span class="font-bold text-sm sm:text-base print:text-slate-900">{{ group.customerName }}</span>
          </div>
          <div class="text-xs text-slate-300 print:text-slate-600 flex flex-wrap gap-x-3 gap-y-1">
            <span v-if="group.customerPhone !== '-'">📞 {{ group.customerPhone }}</span>
            <span v-if="group.customerAddress !== '-'">📍 {{ group.customerAddress }}</span>
          </div>
        </div>
        
        <div class="overflow-x-auto bg-white">
          <table class="w-full text-sm text-left text-slate-500">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
              <tr>
                <th class="px-5 py-3 w-16 text-center text-xs font-bold uppercase tracking-wider">No</th>
                <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider">No Transaksi (Nota)</th>
                <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider">Nama Barang</th>
                <th class="px-5 py-3 w-40 text-right text-xs font-bold uppercase tracking-wider">Kuantitas</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(item, i) in group.items" :key="item.id" class="hover:bg-slate-50/20">
                <td class="px-5 py-3.5 text-center font-medium text-slate-400">{{ i + 1 }}</td>
                <td class="px-5 py-3.5 align-middle">
                  <span class="px-2 py-1 bg-slate-100 rounded text-slate-700 font-mono text-xs font-semibold border border-slate-200/40">
                    {{ item.sale_detail?.sale?.invoice_number }}
                  </span>
                </td>
                <td class="px-5 py-3.5 align-middle font-medium text-slate-800">
                  {{ item.sale_detail?.product?.name }}
                </td>
                <td class="px-5 py-3.5 align-middle text-right font-bold text-slate-800 text-base">
                  {{ Number(item.quantity) }}
                  <span class="text-xs text-slate-400 font-normal ml-1">{{ item.sale_detail?.unit?.name }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Signatures -->
      <div class="mt-20 grid grid-cols-3 gap-6 text-center text-xs text-slate-600">
        <div class="flex flex-col items-center">
          <p class="font-medium text-slate-400 uppercase tracking-wider text-[10px] mb-20">Penerima (Pelanggan)</p>
          <div class="border-t border-slate-300 w-44 pt-2">
            <p class="font-medium text-slate-800">( ........................................ )</p>
          </div>
        </div>
        <div class="flex flex-col items-center">
          <p class="font-medium text-slate-400 uppercase tracking-wider text-[10px] mb-20">Sopir / Pengirim</p>
          <div class="border-t border-slate-300 w-44 pt-2">
            <p class="font-bold text-slate-800">{{ delivery.driver?.name }}</p>
          </div>
        </div>
        <div class="flex flex-col items-center">
          <p class="font-medium text-slate-400 uppercase tracking-wider text-[10px] mb-20">Hormat Kami</p>
          <div class="border-t border-slate-300 w-44 pt-2">
            <p class="font-medium text-slate-800">( ........................................ )</p>
          </div>
        </div>
      </div>

      <!-- Buttons (Hidden on Print) -->
      <div class="print-actions flex justify-center gap-3 mt-16 mb-8 print:hidden">
        <button @click="$router.push({ name: 'Deliveries' })" class="inline-flex items-center gap-1.5 px-6 py-2.5 text-sm font-semibold rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
          Kembali
        </button>
        <button @click="doPrint" class="inline-flex items-center gap-1.5 px-8 py-2.5 text-sm font-semibold rounded-xl bg-primary-600 text-white hover:bg-primary-700 transition-all shadow-md focus:ring-4 focus:ring-primary-100">
          Cetak Surat Jalan
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'

const route = useRoute()
const toast = useToast()

const loading = ref(true)
const delivery = ref(null)
const store = ref(null)

const fetchStoreInfo = async () => {
  try {
    const { data } = await api.get('/store')
    store.value = data
  } catch (e) {
    //
  }
}

const fetchDelivery = async () => {
  try {
    const { data } = await api.get(`/deliveries/${route.params.id}`)
    delivery.value = data
  } catch (e) {
    toast.error('Gagal mengambil detail pengiriman')
  } finally {
    loading.value = false
    nextTick(() => {
      setTimeout(() => {
        window.print()
      }, 300)
    })
  }
}

const groupedItems = computed(() => {
  if (!delivery.value || !delivery.value.items) return []
  const groups = {}
  
  delivery.value.items.forEach(item => {
    const saleDetail = item.sale_detail || {}
    const sale = saleDetail.sale || {}
    
    const customerKey = sale.customer_name || 'Pelanggan Umum'
    
    if (!groups[customerKey]) {
      groups[customerKey] = {
        customerName: customerKey,
        customerPhone: sale.customer_phone || '-',
        customerAddress: sale.customer_address || '-',
        items: []
      }
    }
    
    groups[customerKey].items.push(item)
  })
  
  return Object.values(groups)
})

const doPrint = () => {
  window.print()
}

onMounted(() => {
  fetchStoreInfo()
  fetchDelivery()
})
</script>

<style>
@media print {
  html, body, #app, main, .print-container {
    background: white !important;
    background-color: white !important;
    color: #0f172a !important; /* slate-900 */
    padding: 0 !important;
    margin: 0 !important;
    max-width: 100% !important;
    width: 100% !important;
    box-shadow: none !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  /* Hide sidebar and navbar from layout */
  aside, header, .print\:hidden {
    display: none !important;
  }
}
</style>
