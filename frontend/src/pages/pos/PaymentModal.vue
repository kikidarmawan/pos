<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <!-- Success modal: print receipt? -->
    <div v-if="showSuccessModal" class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-4">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="text-xl font-bold mb-2">Transaksi Berhasil!</h2>
        <p class="text-gray-600 mb-6">Invoice: {{ completedSale?.invoice_number }}</p>
        <p class="text-gray-700 mb-6">Apakah Anda ingin mencetak struk?</p>
        <div class="flex gap-3">
          <button type="button" @click="handlePrintReceipt" class="flex-1 btn btn-success">
            Ya, Cetak Struk
          </button>
          <button type="button" @click="handleCloseSuccess" class="flex-1 btn btn-secondary">
            Tidak
          </button>
        </div>
      </div>
    </div>

    <!-- Payment form -->
    <div v-else class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <h2 class="text-2xl font-bold mb-6">Pembayaran</h2>

      <form @submit.prevent="handlePayment" class="space-y-4">
        <div>
          <label class="label">Total Pembayaran</label>
          <input :value="formatCurrency(total)" type="text" disabled
            class="input font-bold text-2xl text-center text-primary-600" />
        </div>

        <div>
          <label class="label">Metode Pembayaran</label>
          <div class="grid grid-cols-3 gap-2">
            <button
              v-for="opt in paymentMethods"
              :key="opt.value"
              type="button"
              @click="selectPaymentMethod(opt.value)"
              :class="[
                'btn btn-outline text-sm py-2',
                form.payment_method === opt.value
                  ? 'border-primary-600 bg-primary-50 text-primary-700 ring-2 ring-primary-300'
                  : 'border-gray-300 hover:border-primary-400'
              ]"
            >
              {{ opt.label }}
            </button>
          </div>
          <p v-if="form.payment_method === 'credit'" class="text-xs text-amber-600 mt-2">
            Pelanggan bisa ambil barang dulu, bayar nanti atau bayar sebagian
          </p>
        </div>

        <div>
          <label class="label">Jumlah Dibayar</label>
          <input v-model.number="form.paid" type="number" step="1" required min="0"
            class="input text-xl font-medium" />
          <p v-if="isCredit && form.paid < total" class="text-xs text-gray-500 mt-1">
            Bayar sebagian atau 0 untuk utang penuh
          </p>
        </div>

        <div>
          <label class="label">{{ isCredit && form.paid < total ? 'Sisa Utang' : 'Kembalian' }}</label>
          <input :value="displayAmount" type="text" disabled class="input text-xl font-bold"
            :class="amountClass" />
        </div>

        <div class="flex gap-3 pt-4">
          <button type="button" @click="$emit('close')" class="flex-1 btn btn-secondary">
            Batal
          </button>
          <button type="submit" :disabled="loading || !canSubmit" class="flex-1 btn btn-success">
            {{ loading ? 'Memproses...' : 'Selesaikan' }}
          </button>
        </div>
      </form>

      <!-- Quick Amount Buttons -->
      <div class="grid grid-cols-3 gap-2 mt-4">
        <template v-if="isCredit">
          <button @click="form.paid = 0" type="button"
            class="btn btn-outline text-sm border-amber-500 text-amber-600">
            Utang Penuh (0)
          </button>
          <button v-for="amt in partialAmounts" :key="'p-' + amt" @click="form.paid = amt" type="button"
            class="btn btn-outline text-sm">
            {{ formatCurrency(amt) }}
          </button>
        </template>
        <template v-else-if="form.payment_method === 'cash'">
          <button @click="form.paid = total" type="button"
            class="btn btn-outline text-sm border-green-500 text-green-600">
            Uang Pas
          </button>
          <button v-for="amount in quickAmounts" :key="amount" @click="form.paid = amount" type="button"
            class="btn btn-outline text-sm">
            {{ formatCurrency(amount) }}
          </button>
        </template>
        <template v-else>
          <button v-for="amount in quickAmounts" :key="amount" @click="form.paid = amount" type="button"
            class="btn btn-outline text-sm">
            {{ formatCurrency(amount) }}
          </button>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useCartStore } from '@/stores/cart'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency } from '@/utils/format'
import { printReceipt } from '@/utils/printReceipt'

const props = defineProps({
  total: Number,
  warehouseId: [String, Number]
})

const emit = defineEmits(['close', 'success'])

const cartStore = useCartStore()
const toast = useToast()

const loading = ref(false)
const showSuccessModal = ref(false)
const completedSale = ref(null)
const form = ref({
  payment_method: 'cash',
  paid: props.total
})

const isCredit = computed(() => form.value.payment_method === 'credit')

const change = computed(() => Math.max(0, form.value.paid - props.total))

const debt = computed(() => Math.max(0, props.total - form.value.paid))

const displayAmount = computed(() => {
  if (isCredit.value && form.value.paid < props.total) {
    return formatCurrency(debt.value)
  }
  return formatCurrency(change.value)
})

const amountClass = computed(() => {
  if (isCredit.value && form.value.paid < props.total) {
    return 'text-amber-600'
  }
  return change.value > 0 ? 'text-green-600' : ''
})

const canSubmit = computed(() => {
  if (isCredit.value) return true
  return form.value.paid >= props.total
})

const partialAmounts = computed(() => {
  if (!isCredit.value) return []
  const t = props.total
  return [Math.floor(t * 0.25), Math.floor(t * 0.5), Math.floor(t * 0.75)].filter((a) => a > 0)
})

const quickAmounts = computed(() => {
  const amounts = []
  const rounded = Math.ceil(props.total / 10000) * 10000
  amounts.push(props.total)
  amounts.push(rounded)
  amounts.push(rounded + 10000)
  amounts.push(rounded + 20000)
  amounts.push(rounded + 50000)
  amounts.push(100000)
  return [...new Set(amounts)].sort((a, b) => a - b).slice(0, 6)
})

const paymentMethods = [
  { value: 'cash', label: 'Tunai' },
  { value: 'card', label: 'Kartu' },
  { value: 'transfer', label: 'Transfer' },
  { value: 'credit', label: 'Utang' },
  { value: 'cod', label: 'COD' },
  { value: 'other', label: 'Lainnya' },
]

const selectPaymentMethod = (value) => {
  form.value.payment_method = value
  if (value === 'credit') {
    form.value.paid = 0
  } else {
    form.value.paid = props.total
  }
}

const handlePayment = async () => {
  if (!isCredit.value && form.value.paid < props.total) {
    toast.error('Jumlah pembayaran kurang')
    return
  }

  loading.value = true

  try {
    const payload = {
      warehouse_id: props.warehouseId,
      customer_id: cartStore.customer.id || null,
      customer_name: cartStore.customer.name,
      customer_phone: cartStore.customer.phone,
      customer_address: cartStore.customer.address || null,
      sale_date: (() => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; })(),
      subtotal: cartStore.subtotal,
      tax: cartStore.taxAmount,
      discount: cartStore.discount,
      total: cartStore.total,
      paid: form.value.paid,
      change: isCredit.value && form.value.paid < props.total ? 0 : change.value,
      payment_method: form.value.payment_method,
      details: cartStore.items.map(item => ({
        product_id: item.product.id,
        unit_id: item.unit.id,
        quantity: item.quantity,
        price: item.price,
        subtotal: item.subtotal
      }))
    }

    const response = await api.post('/sales', payload)
    const sale = response.data.sale

    completedSale.value = sale
    showSuccessModal.value = true
  } catch (error) {
    const data = error.response?.data
    const message = data?.error || data?.message || 'Gagal memproses transaksi'
    toast.error(message)
  } finally {
    loading.value = false
  }
}

const handlePrintReceipt = async () => {
  if (!completedSale.value) {
    handleCloseSuccess()
    return
  }
  try {
    await printReceipt(completedSale.value)
    toast.success('Struk berhasil dicetak ke printer')
  } catch (err) {
    toast.error(err.response?.data?.message || err.message || 'Gagal cetak.')
  }
  handleCloseSuccess()
}

const handleCloseSuccess = () => {
  toast.success(`Transaksi selesai! Invoice: ${completedSale.value?.invoice_number}`)
  emit('success', completedSale.value)
  showSuccessModal.value = false
  completedSale.value = null
}
</script>
