<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <router-link to="/sale-returns" class="text-gray-600 hover:text-gray-900">
        <ArrowLeftIcon class="w-5 h-5" />
      </router-link>
      <h1 class="text-3xl font-bold text-gray-900">Buat Retur Penjualan</h1>
    </div>

    <!-- Step 1: Pilih Penjualan -->
    <div v-if="!selectedSale" class="card mb-6">
      <h2 class="text-lg font-semibold mb-4">Pilih Transaksi Penjualan</h2>
      <div class="flex gap-3 mb-4">
        <input v-model="invoiceSearch" type="text" placeholder="No. Invoice (contoh: POS-26060001)" class="input flex-1" @keyup.enter="searchSales" />
        <button type="button" @click="searchSales" class="btn btn-primary">Cari</button>
      </div>
      <div v-if="salesSearchResult.length > 0" class="border rounded overflow-hidden">
        <table class="table">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Tanggal</th>
              <th>Pelanggan</th>
              <th>Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in salesSearchResult" :key="s.id">
              <td class="font-medium">{{ s.invoice_number }}</td>
              <td>{{ formatDate(s.sale_date || s.created_at) }}</td>
              <td>{{ s.customer_name || 'Umum' }}</td>
              <td>{{ formatCurrency(s.total) }}</td>
              <td>
                <button type="button" @click="selectSale(s.id)" class="btn btn-sm btn-primary">Pilih</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else-if="searched && salesSearchResult.length === 0" class="text-gray-500 text-sm">Tidak ada penjualan dengan invoice tersebut.</p>
    </div>

    <!-- Step 2: Form Retur -->
    <div v-else class="card">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Retur dari {{ selectedSale.invoice_number }}</h2>
        <button type="button" @click="selectedSale = null; returnDetails = []" class="btn btn-sm btn-outline">Ganti Penjualan</button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Tanggal Retur *</label>
            <input v-model="form.return_date" type="date" required class="input" />
          </div>
          <div>
            <label class="label">Catatan</label>
            <input v-model="form.notes" type="text" class="input" placeholder="Opsional" />
          </div>
        </div>

        <div>
          <h3 class="font-semibold mb-3">Item yang diretur (isi qty, maks sesuai sisa)</h3>
          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th>Produk</th>
                  <th>Satuan</th>
                  <th class="text-right">Harga</th>
                  <th class="text-right">Maks Retur</th>
                  <th class="text-right">Qty Retur *</th>
                  <th class="text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in returnDetails" :key="row.sale_detail_id">
                  <td>{{ row.product?.name }}</td>
                  <td>{{ row.unit?.name }}</td>
                  <td class="text-right">{{ formatCurrency(row.price) }}</td>
                  <td class="text-right">{{ formatStock(row.returnable_quantity) }}</td>
                  <td class="text-right">
                    <input v-model.number="row.return_quantity" type="number" step="0.001" min="0" :max="row.returnable_quantity"
                      class="input w-24 text-right" @input="recalcSubtotal(idx)" />
                  </td>
                  <td class="text-right font-medium">{{ formatCurrency(row.subtotal) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="returnDetails.length === 0" class="text-gray-500 text-sm">Semua item pada transaksi ini sudah diretur seluruhnya.</p>
        </div>

        <div v-if="totalReturn > 0" class="flex justify-between items-center pt-4 border-t">
          <span class="text-lg font-semibold">Total Retur</span>
          <span class="text-xl font-bold">{{ formatCurrency(totalReturn) }}</span>
        </div>

        <div class="flex gap-3 pt-4">
          <router-link to="/sale-returns" class="btn btn-secondary">Batal</router-link>
          <button type="submit" class="btn btn-primary" :disabled="submitting || totalReturn <= 0">
            {{ submitting ? 'Menyimpan...' : 'Simpan Retur' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatDate } from '@/utils/format'
import { formatStock } from '@/utils/format'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const toast = useToast()
const invoiceSearch = ref('')
const salesSearchResult = ref([])
const searched = ref(false)
const selectedSale = ref(null)
const returnDetails = ref([])
const submitting = ref(false)

const form = reactive({
  return_date: new Date().toISOString().slice(0, 10),
  notes: ''
})

const totalReturn = computed(() => returnDetails.value.reduce((sum, r) => sum + Number(r.subtotal || 0), 0))

async function searchSales() {
  if (!invoiceSearch.value.trim()) return
  searched.value = true
  try {
    const res = await api.get('/sales', { params: { search: invoiceSearch.value.trim(), per_page: 20 } })
    salesSearchResult.value = res.data.data ?? res.data
  } catch (e) {
    toast.error('Gagal mencari penjualan')
  }
}

async function selectSale(saleId) {
  try {
    const res = await api.get(`/sales/${saleId}`)
    const sale = res.data
    if (sale.status === 'cancelled') {
      toast.error('Penjualan sudah dibatalkan')
      return
    }
    selectedSale.value = sale
    returnDetails.value = (sale.details || [])
      .filter((d) => Number(d.returnable_quantity || 0) > 0)
      .map((d) => ({
        sale_detail_id: d.id,
        product_id: d.product_id,
        unit_id: d.unit_id,
        product: d.product,
        unit: d.unit,
        price: d.price,
        returnable_quantity: d.returnable_quantity,
        return_quantity: 0,
        subtotal: 0
      }))
  } catch (e) {
    toast.error('Gagal memuat detail penjualan')
  }
}

function recalcSubtotal(idx) {
  const row = returnDetails.value[idx]
  if (!row) return
  const qty = Math.max(0, Math.min(Number(row.return_quantity) || 0, Number(row.returnable_quantity) || 0))
  row.return_quantity = qty
  row.subtotal = qty * Number(row.price || 0)
}

async function handleSubmit() {
  const details = returnDetails.value
    .filter((r) => Number(r.return_quantity) > 0)
    .map((r) => ({
      sale_detail_id: r.sale_detail_id,
      product_id: r.product_id,
      unit_id: r.unit_id,
      quantity: r.return_quantity,
      price: r.price,
      subtotal: r.subtotal
    }))
  if (details.length === 0) {
    toast.error('Pilih minimal satu item dengan qty retur > 0')
    return
  }
  submitting.value = true
  try {
    await api.post('/sale-returns', {
      sale_id: selectedSale.value.id,
      return_date: form.return_date,
      notes: form.notes || null,
      details
    })
    toast.success('Retur penjualan berhasil disimpan')
    router.push('/sale-returns')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan retur')
  } finally {
    submitting.value = false
  }
}
</script>
