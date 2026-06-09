<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <button @click="$router.push({ name: 'Deliveries' })" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200">
        <ArrowLeftIcon class="w-5 h-5 text-gray-600" />
      </button>
      <h1 class="text-2xl font-bold text-gray-800">Tambah Pengiriman</h1>
    </div>

    <form @submit.prevent="openSummary" class="space-y-6">
      <!-- Form inputs card -->
      <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Sopir *</label>
          <select v-model="form.driver_id" required class="input mt-1.5 w-full bg-white border-slate-200 hover:border-slate-300 focus:border-primary-500 focus:ring-primary-200 transition-all shadow-sm rounded-xl text-sm py-2.5">
            <option value="" disabled>Pilih Sopir</option>
            <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Mobil Kendaraan *</label>
          <select v-model="form.vehicle_id" required class="input mt-1.5 w-full bg-white border-slate-200 hover:border-slate-300 focus:border-primary-500 focus:ring-primary-200 transition-all shadow-sm rounded-xl text-sm py-2.5">
            <option value="" disabled>Pilih Kendaraan</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.name }} ({{ v.license_plate }})</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Catatan</label>
          <textarea v-model="form.notes" rows="2" class="input mt-1.5 w-full bg-white border-slate-200 hover:border-slate-300 focus:border-primary-500 focus:ring-primary-200 transition-all shadow-sm rounded-xl text-sm" placeholder="Opsional"></textarea>
        </div>
      </div>

      <!-- Item selection card -->
      <div class="bg-white rounded-2xl shadow-md border border-slate-100 p-6">
        <div class="flex flex-wrap gap-4 justify-between items-center mb-6">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Pilih Barang yang Dikirim</h2>
            <p class="text-xs text-slate-400 mt-1">Pilih item dari satu nomor transaksi/nota saja untuk satu pengiriman</p>
          </div>
          <div class="relative w-80">
            <input v-model="searchItem" type="text" placeholder="Cari No Nota/Produk..." class="input pl-10 bg-white border-slate-200 hover:border-slate-300 focus:border-primary-500 focus:ring-primary-200 transition-all shadow-sm rounded-lg text-sm" />
            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
          </div>
        </div>

        <div v-if="loadingItems" class="flex flex-col items-center justify-center py-12 gap-2 text-slate-500 border border-dashed border-slate-200 rounded-xl mb-4">
          <div class="spinner w-8 h-8"></div>
          <span>Memuat data item tertunda...</span>
        </div>
        <div v-else-if="groupedPendingItems.length === 0" class="flex flex-col items-center justify-center py-12 text-slate-400 italic border border-dashed border-slate-200 rounded-xl mb-4">
          Tidak ada barang yang perlu dikirim
        </div>

        <div v-else class="space-y-6 max-h-[500px] overflow-y-auto mb-6 border border-slate-100 p-5 rounded-2xl bg-slate-50/50">
          <div v-for="(group, index) in groupedPendingItems" :key="index"
            class="bg-white border border-slate-100 rounded-xl overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md"
            :class="{ 'border-primary-100 ring-1 ring-primary-100/50': activeInvoiceNumber === group.invoiceNumber, 'opacity-60': activeInvoiceNumber && activeInvoiceNumber !== group.invoiceNumber }">
            <div class="bg-slate-50/80 px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <h4 class="font-bold text-slate-800 text-base font-mono bg-white px-2.5 py-1 border border-slate-200/60 rounded shadow-sm inline-block">{{ group.invoiceNumber }}</h4>
                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 mt-2">
                  <div class="font-semibold text-slate-700">
                    👤 {{ group.customerName }}
                  </div>
                  <div v-if="group.customerPhone">
                    📞 {{ group.customerPhone }}
                  </div>
                  <div v-if="group.customerAddress" class="max-w-xs truncate">
                    📍 {{ group.customerAddress }}
                  </div>
                </div>
              </div>
              <label class="flex items-center gap-2 cursor-pointer self-start sm:self-center" :class="{ 'opacity-50 cursor-not-allowed': activeInvoiceNumber && activeInvoiceNumber !== group.invoiceNumber }">
                <input type="checkbox" @change="toggleGroup(group)" :checked="isGroupSelected(group)" :disabled="activeInvoiceNumber && activeInvoiceNumber !== group.invoiceNumber"
                  class="w-4.5 h-4.5 rounded border-slate-300 text-primary-600 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed" />
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Pilih Semua</span>
              </label>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full border-collapse text-sm text-left text-slate-500 mb-0">
                <thead class="bg-slate-50/50 border-b border-slate-100">
                  <tr>
                    <th class="w-12 text-center py-3 pl-4"></th>
                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Produk</th>
                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-32">Sisa Kirim</th>
                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-40">Qty Dikirim</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="item in group.items" :key="item.id" class="hover:bg-slate-50/20 transition-colors duration-150" :class="{ 'bg-blue-50/20': isSelected(item) }">
                    <td class="text-center py-3.5 pl-4 align-middle">
                      <input type="checkbox" :checked="isSelected(item)" @change="toggleSelection(item)" :disabled="activeInvoiceNumber && activeInvoiceNumber !== group.invoiceNumber"
                        class="w-4.5 h-4.5 rounded border-slate-300 text-primary-600 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed" />
                    </td>
                    <td class="px-5 py-3.5 text-sm font-semibold text-slate-700 align-middle">
                      {{ item.product.name }}
                      <span class="text-slate-400 font-normal text-xs ml-1">({{ item.unit.name }})</span>
                    </td>
                    <td class="px-5 py-3.5 align-middle">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100 shadow-sm">{{ item.remaining_qty }}</span>
                    </td>
                    <td class="px-5 py-3.5 align-middle">
                      <div class="relative flex items-center">
                        <input type="number" step="0.001" min="0.001" :max="item.remaining_qty"
                          class="input w-full py-1.5 px-3 pr-12 text-sm text-slate-800 font-semibold bg-white border border-slate-200 rounded-lg hover:border-slate-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition-all disabled:bg-slate-50 disabled:cursor-not-allowed" :disabled="!isSelected(item)"
                          v-model.number="getSelection(item).quantity" @input="handleQtyInput(item)" />
                        <span class="absolute right-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider select-none">{{ item.unit.name }}</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-5 border-t border-slate-100">
          <div class="text-sm font-medium text-slate-500 flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span>
            Terpilih: <strong class="text-slate-800">{{ selectedItems.length }}</strong> item dari nota <strong class="text-primary-600 font-mono">{{ activeInvoiceNumber || '-' }}</strong>
          </div>
          
          <div class="flex gap-3">
            <button type="button" @click="$router.push({ name: 'Deliveries' })"
              class="inline-flex items-center gap-1.5 px-6 py-2.5 text-sm font-semibold rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
              Batal
            </button>
            <button type="submit" class="inline-flex items-center gap-1.5 px-8 py-2.5 text-sm font-semibold rounded-xl bg-primary-600 text-white hover:bg-primary-700 transition-all shadow-md focus:ring-4 focus:ring-primary-100 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="submitting || selectedItems.length === 0">
              {{ submitting ? 'Menyimpan...' : 'Simpan Pengiriman' }}
            </button>
          </div>
        </div>
      </div>
    </form>

    <!-- Summary Modal -->
    <div v-if="showSummaryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto" @click.self="showSummaryModal = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-8 m-4 relative overflow-hidden transition-all transform scale-100">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-primary-500 via-primary-600 to-indigo-600"></div>
        
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shadow-sm">
            <TruckIcon class="w-5 h-5" />
          </div>
          <div>
            <h2 class="text-xl font-extrabold text-slate-800 tracking-tight">Ringkasan Pengiriman</h2>
            <p class="text-xs text-slate-400 mt-0.5">Harap periksa kembali detail rencana pengiriman sebelum menyimpan</p>
          </div>
        </div>
        <!-- Driver & Vehicle Card info -->
        <div class="bg-slate-50/80 border border-slate-100 rounded-2xl p-5 grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6 shadow-sm">
          <div class="sm:col-span-2 bg-blue-50/50 border border-blue-100/50 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-inner">
            <div>
              <span class="text-[10px] text-blue-500 font-bold uppercase tracking-widest block">No Transaksi / Nota</span>
              <span class="font-mono font-bold text-blue-900 text-sm mt-1 bg-white px-2 py-0.5 border border-blue-100 rounded shadow-sm inline-block">{{ activeInvoiceNumber || '-' }}</span>
            </div>
            <div class="sm:text-right">
              <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest block">Pelanggan</span>
              <span class="font-bold text-slate-700 text-sm mt-1 block">{{ selectedItemsDetails[0]?.customerName || 'Pelanggan Umum' }}</span>
            </div>
          </div>
          <div class="flex gap-3 items-center">
            <div class="w-9 h-9 rounded-full bg-white border border-slate-200/60 flex items-center justify-center text-slate-500 shadow-sm">
              <UserIcon class="w-4.5 h-4.5" />
            </div>
            <div>
              <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest block">Sopir</span>
              <span class="font-bold text-slate-700 text-sm">{{ selectedDriver?.name || '-' }}</span>
            </div>
          </div>
          <div class="flex gap-3 items-center">
            <div class="w-9 h-9 rounded-full bg-white border border-slate-200/60 flex items-center justify-center text-slate-500 shadow-sm">
              <TruckIcon class="w-4.5 h-4.5" />
            </div>
            <div>
              <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest block">Kendaraan</span>
              <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                <span class="font-bold text-slate-700 text-sm">{{ selectedVehicle?.name || '-' }}</span>
                <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-mono font-bold bg-slate-800 text-white rounded border border-slate-900 shadow-sm tracking-wide">
                  {{ selectedVehicle?.license_plate }}
                </span>
              </div>
            </div>
          </div>
          <div class="sm:col-span-2 bg-white border border-slate-200/50 rounded-xl p-3.5" v-if="form.notes">
            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest block mb-1">Catatan Tambahan</span>
            <span class="text-xs text-slate-600 italic font-medium">"{{ form.notes }}"</span>
          </div>
        </div>

        <!-- Selected Items Table -->
        <h3 class="font-semibold text-slate-700 mb-2 text-sm">Daftar Barang yang Dikirim</h3>
        <div class="border border-slate-100 rounded-xl overflow-hidden max-h-60 overflow-y-auto mb-6 shadow-sm">
          <table class="w-full text-sm text-left text-slate-500">
            <thead class="bg-slate-50/70 border-b border-slate-200/60 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
              <tr>
                <th class="px-5 py-3 w-32">Kode Item</th>
                <th class="px-5 py-3">Produk</th>
                <th class="px-5 py-3 text-right">Qty Dikirim</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="item in selectedItemsDetails" :key="item.sale_detail_id" class="hover:bg-slate-50/40">
                <td class="px-5 py-3.5 align-middle font-mono font-semibold text-slate-700 text-xs">{{ item.productCode }}</td>
                <td class="px-5 py-3.5 align-middle">
                  <div class="font-semibold text-slate-800 text-sm">{{ item.productName }}</div>
                </td>
                <td class="px-5 py-3.5 align-middle text-right font-extrabold text-slate-800 text-sm">
                  {{ item.quantity }}
                  <span class="text-[10px] text-slate-400 font-normal ml-1">{{ item.unitName }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex gap-3 justify-end pt-5 border-t border-slate-100">
          <button type="button" @click="showSummaryModal = false" class="inline-flex items-center gap-1.5 px-6 py-2.5 text-sm font-semibold rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            Kembali
          </button>
          <button type="button" @click="executeSubmit" :disabled="submitting" class="inline-flex items-center gap-1.5 px-8 py-2.5 text-sm font-semibold rounded-xl bg-primary-600 text-white hover:bg-primary-700 transition-all shadow-md focus:ring-4 focus:ring-primary-100">
            <span v-if="submitting" class="spinner w-4 h-4 !border-2 !border-t-white"></span>
            {{ submitting ? 'Menyimpan...' : 'Konfirmasi & Simpan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { ArrowLeftIcon, MagnifyingGlassIcon, PhoneIcon, MapPinIcon, TruckIcon, UserIcon } from '@heroicons/vue/24/outline'
import api from '@/utils/axios'

const router = useRouter()
const toast = useToast()

const drivers = ref([])
const vehicles = ref([])
const pendingItems = ref([])
const loadingItems = ref(false)
const submitting = ref(false)
const searchItem = ref('')

const form = reactive({
  driver_id: '',
  vehicle_id: '',
  notes: ''
})

// array of { sale_detail_id, quantity, remaining_qty }
const selectedItems = ref([])

onMounted(async () => {
  fetchDrivers()
  fetchVehicles()
  fetchPendingItems()
})

const fetchDrivers = async () => {
  try {
    const { data } = await api.get('/drivers', { params: { per_page: 100 } })
    drivers.value = data.data
  } catch (e) { }
}

const fetchVehicles = async () => {
  try {
    const { data } = await api.get('/vehicles', { params: { per_page: 100 } })
    vehicles.value = data.data
  } catch (e) { }
}

const fetchPendingItems = async () => {
  loadingItems.value = true
  try {
    const { data } = await api.get('/deliveries/pending-items')
    pendingItems.value = data
  } catch (e) {
    toast.error('Gagal memuat barang tertunda')
  } finally {
    loadingItems.value = false
  }
}

const filteredPendingItems = computed(() => {
  if (!searchItem.value) return pendingItems.value
  const q = searchItem.value.toLowerCase()
  return pendingItems.value.filter(i =>
    i.sale.invoice_number.toLowerCase().includes(q) ||
    i.product.name.toLowerCase().includes(q) ||
    (i.sale.customer_name && i.sale.customer_name.toLowerCase().includes(q))
  )
})

const groupedPendingItems = computed(() => {
  const groups = {}

  filteredPendingItems.value.forEach(item => {
    const sale = item.sale || {}
    const invoiceNumber = sale.invoice_number || 'Tidak Ada Nota'

    if (!groups[invoiceNumber]) {
      groups[invoiceNumber] = {
        invoiceNumber: invoiceNumber,
        customerName: sale.customer_name || 'Pelanggan Umum',
        customerPhone: sale.customer_phone || '',
        customerAddress: sale.customer_address || '',
        items: []
      }
    }

    if (!groups[invoiceNumber].customerPhone && sale.customer_phone) {
      groups[invoiceNumber].customerPhone = sale.customer_phone
    }
    if (!groups[invoiceNumber].customerAddress && sale.customer_address) {
      groups[invoiceNumber].customerAddress = sale.customer_address
    }

    groups[invoiceNumber].items.push(item)
  })

  return Object.values(groups)
})

const getSelection = (item) => {
  let sel = selectedItems.value.find(i => i.sale_detail_id === item.id)
  if (!sel) {
    // Return dummy object if not selected to avoid v-model errors
    return { quantity: item.remaining_qty }
  }
  return sel
}

const isSelected = (item) => {
  return selectedItems.value.some(i => i.sale_detail_id === item.id)
}

const toggleSelection = (item) => {
  const index = selectedItems.value.findIndex(i => i.sale_detail_id === item.id)
  if (index >= 0) {
    selectedItems.value.splice(index, 1)
  } else {
    selectedItems.value.push({
      sale_detail_id: item.id,
      quantity: item.remaining_qty,
      remaining_qty: item.remaining_qty
    })
  }
}

const isGroupSelected = (group) => {
  if (group.items.length === 0) return false;
  return group.items.every(item => isSelected(item));
}

const toggleGroup = (group) => {
  if (isGroupSelected(group)) {
    // Deselect all items in group
    group.items.forEach(item => {
      const index = selectedItems.value.findIndex(i => i.sale_detail_id === item.id)
      if (index >= 0) selectedItems.value.splice(index, 1)
    })
  } else {
    // Select all items in group
    group.items.forEach(item => {
      if (!isSelected(item)) {
        selectedItems.value.push({
          sale_detail_id: item.id,
          quantity: item.remaining_qty,
          remaining_qty: item.remaining_qty
        })
      }
    })
  }
}

const activeInvoiceNumber = computed(() => {
  if (selectedItems.value.length === 0) return null
  const firstSel = selectedItems.value[0]
  const firstItem = pendingItems.value.find(i => i.id === firstSel.sale_detail_id)
  return firstItem?.sale?.invoice_number || null
})

const activeSaleId = computed(() => {
  if (selectedItems.value.length === 0) return null
  const firstSel = selectedItems.value[0]
  const firstItem = pendingItems.value.find(i => i.id === firstSel.sale_detail_id)
  return firstItem?.sale?.id || null
})

const handleQtyInput = (item) => {
  let sel = selectedItems.value.find(i => i.sale_detail_id === item.id)
  if (sel) {
    if (sel.quantity > sel.remaining_qty) {
      sel.quantity = sel.remaining_qty
      toast.warning(`Maksimal pengiriman: ${sel.remaining_qty}`)
    }
    if (sel.quantity < 0.001) {
      sel.quantity = 0.001
    }
  }
}

const showSummaryModal = ref(false)

const selectedDriver = computed(() => drivers.value.find(d => d.id === form.driver_id))
const selectedVehicle = computed(() => vehicles.value.find(v => v.id === form.vehicle_id))

const selectedItemsDetails = computed(() => {
  return selectedItems.value.map(sel => {
    const originalItem = pendingItems.value.find(i => i.id === sel.sale_detail_id)
    return {
      ...sel,
      productName: originalItem?.product?.name || 'Produk Tidak Dikenal',
      productCode: originalItem?.product?.code || '-',
      unitName: originalItem?.unit?.name || '',
      invoiceNumber: originalItem?.sale?.invoice_number || 'Tidak Ada Nota',
      customerName: originalItem?.sale?.customer_name || 'Pelanggan Umum'
    }
  })
})

const openSummary = () => {
  if (selectedItems.value.length === 0) {
    toast.error('Pilih setidaknya satu barang untuk dikirim')
    return
  }
  showSummaryModal.value = true
}

const executeSubmit = async () => {
  if (selectedItems.value.length === 0) {
    toast.error('Pilih setidaknya satu barang untuk dikirim')
    return
  }

  submitting.value = true
  try {
    const payload = {
      ...form,
      sale_id: activeSaleId.value,
      items: selectedItems.value.map(i => ({
        sale_detail_id: i.sale_detail_id,
        quantity: i.quantity
      }))
    }
    await api.post('/deliveries', payload)
    toast.success('Pengiriman berhasil dibuat')
    showSummaryModal.value = false
    router.push({ name: 'Deliveries' })
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan pengiriman')
  } finally {
    submitting.value = false
  }
}
</script>
