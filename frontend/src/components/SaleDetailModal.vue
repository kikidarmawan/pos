<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[92vh] overflow-hidden flex flex-col">
      <div class="p-4 border-b flex justify-between items-center shrink-0">
        <h2 class="text-xl font-bold">Detail Transaksi</h2>
        <button type="button" @click="emit('close')" class="p-2 text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div class="p-4 overflow-y-auto flex-1">
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-10 w-10 border-2 border-primary-600 border-t-transparent"></div>
          <p class="mt-2 text-gray-500">Memuat detail...</p>
        </div>

        <div v-else-if="sale" class="space-y-4">
          <!-- Header -->
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="text-gray-500">Invoice:</span>
              <span class="font-medium ml-2">{{ sale.invoice_number }}</span>
            </div>
            <div>
              <span class="text-gray-500">Tanggal & Jam:</span>
              <span class="ml-2">{{ formatDateTime(sale.created_at || sale.sale_date) }}</span>
            </div>
            <div>
              <span class="text-gray-500">Kasir:</span>
              <span class="ml-2">{{ sale.user?.name || '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500">Gudang:</span>
              <span class="ml-2">{{ sale.warehouse?.name || '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500">Metode:</span>
              <span class="ml-2">{{ formatPaymentMethod(sale.payment_method) }}</span>
            </div>
            <div>
              <span class="text-gray-500">Status:</span>
              <span :class="['ml-2 badge', sale.status === 'completed' ? 'badge-success' : 'badge-danger']">
                {{ sale.status === 'completed' ? 'Selesai' : 'Dibatalkan' }}
              </span>
            </div>
          </div>

          <!-- Customer -->
          <div class="border-t pt-4">
            <div class="flex justify-between items-center mb-2">
              <h3 class="font-medium text-gray-700">Pelanggan</h3>
              <button 
                v-if="!sale.is_customer_edited && sale.status !== 'cancelled' && !isEditingCustomer" 
                type="button" 
                @click="startEditCustomer" 
                class="text-sm text-primary-600 hover:text-primary-700 flex items-center gap-1 font-medium"
              >
                <PencilSquareIcon class="w-4 h-4" />
                Ubah
              </button>
              <span v-if="sale.is_customer_edited" class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                Sudah diubah (Batas edit: 1x)
              </span>
            </div>

            <div v-if="!isEditingCustomer">
              <p class="font-medium">{{ sale.customer_name || 'Umum' }}</p>
              <p v-if="sale.customer_phone" class="text-sm text-gray-600 mt-0.5">{{ sale.customer_phone }}</p>
              <p v-if="sale.customer_address" class="text-sm text-gray-500 mt-1">{{ sale.customer_address }}</p>
            </div>

            <div v-else class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-3 mt-2">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Pilih Pelanggan Terdaftar</label>
                <select 
                  :value="editForm.customer_id || ''" 
                  @change="onCustomerSelect" 
                  class="input text-sm w-full"
                  :disabled="customerLoading"
                >
                  <option value="">-- Umum / Non-Member --</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">
                    {{ c.name }} ({{ c.phone || 'No telp -' }})
                  </option>
                </select>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Pelanggan</label>
                  <input v-model="editForm.customer_name" type="text" class="input text-sm w-full" placeholder="Nama pelanggan" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1">No. Telepon</label>
                  <input v-model="editForm.customer_phone" type="text" class="input text-sm w-full" placeholder="No. telepon" />
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat</label>
                <textarea v-model="editForm.customer_address" class="input text-sm w-full h-16 resize-none" placeholder="Alamat pelanggan"></textarea>
              </div>

              <div class="flex gap-2 justify-end pt-1">
                <button type="button" @click="isEditingCustomer = false" class="btn btn-sm btn-outline">
                  Batal
                </button>
                <button type="button" @click="handleSaveCustomer" :disabled="saveCustomerLoading" class="btn btn-sm btn-primary">
                  {{ saveCustomerLoading ? 'Menyimpan...' : 'Simpan' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Items -->
          <div class="border-t pt-4">
            <h3 class="font-medium text-gray-700 mb-3">Item</h3>
            <div class="overflow-x-auto">
              <table class="table text-sm">
                <thead>
                  <tr>
                    <th>Produk</th>
                    <th>Satuan</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Diskon</th>
                    <th class="text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(d, i) in (sale.details || [])" :key="i">
                    <td>{{ d.product?.name || '-' }}</td>
                    <td>{{ d.unit?.name || '-' }}</td>
                    <td class="text-right">{{ formatStock(d.quantity) }}</td>
                    <td class="text-right">{{ formatCurrency(d.price) }}</td>
                    <td class="text-right">{{ formatCurrency(itemDiscount(d)) }}</td>
                    <td class="text-right font-medium">{{ formatCurrency(d.subtotal) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Data Retur (jika ada) -->
          <div v-if="sale.sale_returns?.length" class="border-t pt-4">
            <h3 class="font-medium text-gray-700 mb-3">Data Retur</h3>
            <p class="text-sm text-gray-500 mb-3">Transaksi ini memiliki {{ sale.sale_returns.length }} retur.</p>
            <div v-for="ret in sale.sale_returns" :key="ret.id" class="mb-4 p-4 bg-amber-50 rounded-lg border border-amber-200">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <span class="font-medium">{{ ret.return_number }}</span>
                  <span class="text-gray-500 text-sm ml-2">{{ formatDate(ret.return_date) }}</span>
                </div>
                <span class="font-bold text-amber-700">{{ formatCurrency(ret.total) }}</span>
              </div>
              <div class="overflow-x-auto">
                <table class="table text-sm">
                  <thead>
                    <tr>
                      <th>Produk</th>
                      <th>Satuan</th>
                      <th class="text-right">Qty Retur</th>
                      <th class="text-right">Harga</th>
                      <th class="text-right">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(rd, i) in (ret.details || [])" :key="i">
                      <td>{{ rd.product?.name || '-' }}</td>
                      <td>{{ rd.unit?.name || '-' }}</td>
                      <td class="text-right">{{ formatStock(rd.quantity) }}</td>
                      <td class="text-right">{{ formatCurrency(rd.price) }}</td>
                      <td class="text-right font-medium">{{ formatCurrency(rd.subtotal) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-if="ret.notes" class="text-xs text-gray-600 mt-2">Catatan: {{ ret.notes }}</p>
            </div>
          </div>

          <!-- Summary -->
          <div class="border-t pt-4 space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Subtotal</span>
              <span>{{ formatCurrency(sale.subtotal) }}</span>
            </div>
            <div v-if="(sale.discount || 0) > 0" class="flex justify-between">
              <span class="text-gray-500">Diskon</span>
              <span>-{{ formatCurrency(sale.discount) }}</span>
            </div>
            <div v-if="(sale.tax || 0) > 0" class="flex justify-between">
              <span class="text-gray-500">Pajak</span>
              <span>{{ formatCurrency(sale.tax) }}</span>
            </div>
            <div class="flex justify-between font-bold text-lg pt-2">
              <span>Total</span>
              <span class="text-primary-600">{{ formatCurrency(sale.total) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Bayar</span>
              <span>{{ formatCurrency(sale.paid) }}</span>
            </div>
            <div v-if="(sale.change || 0) > 0" class="flex justify-between">
              <span class="text-gray-500">Kembalian</span>
              <span>{{ formatCurrency(sale.change) }}</span>
            </div>
            <div v-if="hasRemainingDebt" class="flex justify-between text-amber-600 font-medium">
              <span>Sisa Utang</span>
              <span>{{ formatCurrency(remainingDebt) }}</span>
            </div>
          </div>

          <!-- Input Pembayaran (hanya jika belum lunas) -->
          <div v-if="hasRemainingDebt" class="border-t pt-4 bg-amber-50 rounded-lg p-4">
            <h3 class="font-medium text-gray-700 mb-3">Input Pembayaran</h3>
            <p class="text-sm text-gray-600 mb-3">Sisa utang {{ formatCurrency(remainingDebt) }}. Masukkan jumlah pembayaran:</p>
            <div class="flex gap-3 flex-wrap items-end">
              <div class="flex-1 min-w-[160px]">
                <label class="label text-xs">Jumlah Bayar</label>
                <input v-model.number="paymentAmount" type="number" step="1000" min="0"
                  :placeholder="formatCurrency(remainingDebt)" class="input" />
              </div>
              <button type="button" @click="handlePayment" :disabled="paymentLoading || !paymentAmount || paymentAmount <= 0"
                class="btn btn-success">
                {{ paymentLoading ? 'Memproses...' : 'Bayar' }}
              </button>
              <button type="button" @click="paymentAmount = remainingDebt" class="btn btn-outline btn-sm">
                Bayar Penuh
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="p-4 border-t flex gap-3 shrink-0">
        <button v-if="sale && sale.status === 'completed'" type="button" @click="handlePrint"
          class="btn btn-success flex-1">
          <PrinterIcon class="w-5 h-5 mr-2 inline" />
          Cetak Struk
        </button>
        <button type="button" @click="emit('close')" class="btn btn-secondary flex-1">
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatDate, formatDateTime, formatPaymentMethod, formatStock } from '@/utils/format'
import { printReceipt } from '@/utils/printReceipt'
import { XMarkIcon, PrinterIcon, PencilSquareIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  saleId: { type: [Number, String], default: null }
})

const emit = defineEmits(['close', 'updated'])

const toast = useToast()
const loading = ref(false)
const sale = ref(null)
const paymentAmount = ref(0)
const paymentLoading = ref(false)

// Edit Customer States
const isEditingCustomer = ref(false)
const customers = ref([])
const customerLoading = ref(false)
const saveCustomerLoading = ref(false)
const editForm = ref({
  customer_id: null,
  customer_name: '',
  customer_phone: '',
  customer_address: ''
})

const fetchCustomers = async () => {
  if (customers.value.length > 0) return
  customerLoading.value = true
  try {
    const res = await api.get('/customers', { params: { per_page: 200, is_active: 1 } })
    customers.value = res.data.data || []
  } catch (e) {
    console.error('Gagal memuat daftar pelanggan', e)
  } finally {
    customerLoading.value = false
  }
}

const startEditCustomer = async () => {
  if (!sale.value) return
  editForm.value = {
    customer_id: sale.value.customer_id,
    customer_name: sale.value.customer_name || '',
    customer_phone: sale.value.customer_phone || '',
    customer_address: sale.value.customer_address || ''
  }
  isEditingCustomer.value = true
  await fetchCustomers()
}

const onCustomerSelect = (event) => {
  const customerId = event.target.value
  if (!customerId) {
    editForm.value.customer_id = null
    editForm.value.customer_name = 'Umum'
    editForm.value.customer_phone = ''
    editForm.value.customer_address = ''
  } else {
    const cust = customers.value.find(c => c.id == customerId)
    if (cust) {
      editForm.value.customer_id = cust.id
      editForm.value.customer_name = cust.name
      editForm.value.customer_phone = cust.phone || ''
      editForm.value.customer_address = cust.address || ''
    }
  }
}

const handleSaveCustomer = async () => {
  saveCustomerLoading.value = true
  try {
    const res = await api.put(`/sales/${sale.value.id}/customer`, editForm.value)
    toast.success('Data pelanggan berhasil diubah')
    sale.value = { ...sale.value, ...res.data.sale }
    isEditingCustomer.value = false
    emit('updated')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal mengubah data pelanggan')
  } finally {
    saveCustomerLoading.value = false
  }
}

const hasRemainingDebt = computed(() => {
  if (!sale.value || sale.value.status === 'cancelled') return false
  return Number(sale.value.paid || 0) < Number(sale.value.total || 0)
})

const remainingDebt = computed(() => {
  if (!sale.value) return 0
  return Math.max(0, Number(sale.value.total || 0) - Number(sale.value.paid || 0))
})

const fetchSale = async () => {
  if (!props.saleId) return
  loading.value = true
  sale.value = null
  isEditingCustomer.value = false
  try {
    const res = await api.get(`/sales/${props.saleId}`)
    sale.value = res.data
  } catch (e) {
    toast.error('Gagal memuat detail transaksi')
  } finally {
    loading.value = false
  }
}

const itemDiscount = (d) => {
  const before = Number(d.price || 0) * Number(d.quantity || 0)
  const after = Number(d.subtotal || 0)
  return Math.max(0, before - after)
}

watch(() => props.saleId, (id) => {
  if (id) fetchSale()
}, { immediate: true })

const handlePrint = async () => {
  if (!sale.value) return
  try {
    await printReceipt(sale.value)
    toast.success('Struk berhasil dicetak ke printer')
  } catch (err) {
    toast.error(err.response?.data?.message || err.message || 'Gagal cetak.')
  }
}

const handlePayment = async () => {
  if (!sale.value || !paymentAmount.value || paymentAmount.value <= 0) return
  if (paymentAmount.value > remainingDebt.value) {
    toast.error('Jumlah melebihi sisa utang')
    return
  }
  paymentLoading.value = true
  try {
    const res = await api.post(`/sales/${sale.value.id}/payment`, {
      amount: paymentAmount.value
    })
    sale.value = res.data.sale
    paymentAmount.value = 0
    emit('updated')
    toast.success('Pembayaran berhasil dicatat')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal mencatat pembayaran')
  } finally {
    paymentLoading.value = false
  }
}

watch(remainingDebt, (val) => {
  if (val === 0) paymentAmount.value = 0
})
</script>
