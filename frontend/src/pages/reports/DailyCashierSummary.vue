<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6 no-print">Daily Cashier POS Summary</h1>

    <!-- Filters -->
    <div class="card mb-6 no-print">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
        <div>
          <label class="label">Tanggal Mulai *</label>
          <input v-model="filters.start_date" type="date" required class="input" />
        </div>
        <div>
          <label class="label">Tanggal Akhir *</label>
          <input v-model="filters.end_date" type="date" required class="input" />
        </div>
        <div>
          <label class="label">Gudang</label>
          <select v-model="filters.warehouse_id" class="input">
            <option value="">Semua Gudang</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
              {{ wh.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="label">Kasir</label>
          <select v-model="filters.user_id" class="input">
            <option value="">Semua Kasir</option>
            <option v-for="u in users" :key="u.id" :value="u.id">
              {{ u.name }}
            </option>
          </select>
        </div>
        <div class="flex items-end gap-2">
          <button @click="loadReport" class="btn btn-primary flex-1">
            <MagnifyingGlassIcon class="w-5 h-5 mr-2" />
            Tampilkan
          </button>
          <button type="button" @click="exportToPdf" :disabled="!report || pdfLoading" class="btn btn-success" title="Unduh PDF">
            <ArrowDownTrayIcon v-if="!pdfLoading" class="w-5 h-5 mr-2" />
            <span v-else class="animate-spin inline-block w-5 h-5 mr-2 border-2 border-white border-t-transparent rounded-full"></span>
            {{ pdfLoading ? 'Membuat PDF...' : 'Unduh PDF' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="card p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-10 w-10 border-2 border-primary-600 border-t-transparent"></div>
      <p class="mt-2 text-gray-500">Memuat laporan...</p>
    </div>

    <!-- Report (format seperti PDF) -->
    <div v-else-if="report" ref="reportEl" class="card p-6 print:shadow-none">
      <!-- Header -->
      <div class="text-sm text-gray-600 mb-4 space-y-1">
        <p>Date from: {{ dateRangeFormatted }}</p>
        <p class="font-semibold text-gray-900">{{ report.store?.name || 'Toko' }}</p>
        <p>Printed date: {{ printedAt }}</p>
        <p>Printed by: {{ currentUserName }}</p>
      </div>

      <h2 class="text-lg font-bold text-center mb-4 border-b pb-2">DAILY CASHIER REPORT POS SUMMARY</h2>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="border-b bg-gray-100">
              <th class="text-left py-2 px-2 whitespace-nowrap">POS No.</th>
              <th class="text-left py-2 px-2 whitespace-nowrap">Status</th>
              <th class="text-left py-2 px-2 whitespace-nowrap">Remark</th>
              <th class="text-left py-2 px-2 whitespace-nowrap">POS Date</th>
              <th class="text-left py-2 px-2 whitespace-nowrap">Customer Name</th>
              <th class="text-left py-2 px-2 whitespace-nowrap">Payment Method</th>
              <th class="text-right py-2 px-2 whitespace-nowrap">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sale in report.sales" :key="sale.id" class="border-b hover:bg-gray-50">
              <td class="py-2 px-2 font-medium">{{ sale.invoice_number }}</td>
              <td class="py-2 px-2">{{ sale.status === 'completed' ? 'Paid' : sale.status }}</td>
              <td class="py-2 px-2">{{ sale.customer_phone || '-' }}</td>
              <td class="py-2 px-2">{{ formatDateShort(sale.sale_date) }}</td>
              <td class="py-2 px-2">{{ sale.customer_name || 'Umum' }}</td>
              <td class="py-2 px-2">{{ formatPaymentMethod(sale.payment_method) }}</td>
              <td class="py-2 px-2 text-right font-medium">{{ formatNumber(sale.total, 2) }}</td>
            </tr>
            <tr v-if="!report.sales?.length" class="border-b">
              <td colspan="7" class="py-8 text-center text-gray-500">Tidak ada transaksi pada periode ini.</td>
            </tr>
          </tbody>
          <!-- Total row -->
          <tfoot v-if="report.sales?.length">
            <tr class="border-t-2 bg-gray-100 font-bold">
              <td class="py-3 px-2" colspan="6">Total</td>
              <td class="py-3 px-2 text-right">{{ formatNumber(summary.total_revenue, 2) }}</td>
            </tr>
            <tr class="bg-gray-50 text-sm">
              <td class="py-2 px-2" colspan="6">Per Metode Pembayaran</td>
              <td class="py-2 px-2"></td>
            </tr>
            <tr v-for="(amt, method) in summary.by_payment_method" :key="method" class="text-sm">
              <td class="py-1 px-2" colspan="6">{{ formatPaymentMethod(method) }}</td>
              <td class="py-1 px-2 text-right">{{ formatNumber(amt, 2) }}</td>
            </tr>
            <tr v-if="(summary.total_returns || 0) > 0" class="text-sm text-red-600">
              <td class="py-2 px-2" colspan="6">Total Retur</td>
              <td class="py-2 px-2 text-right">-{{ formatNumber(summary.total_returns, 2) }}</td>
            </tr>
            <tr v-if="(summary.total_returns || 0) > 0" class="border-t-2 bg-primary-50 font-bold">
              <td class="py-2 px-2" colspan="6">Pendapatan Bersih</td>
              <td class="py-2 px-2 text-right">{{ formatNumber(summary.net_revenue, 2) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- RJ POS (Retur) -->
      <div class="mt-8">
        <h3 class="text-base font-bold mb-3 border-b pb-2">RJ POS (Retur)</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead>
              <tr class="border-b bg-amber-50">
                <th class="text-left py-2 px-2 whitespace-nowrap">No. Retur</th>
                <th class="text-left py-2 px-2 whitespace-nowrap">Tanggal Retur</th>
                <th class="text-left py-2 px-2 whitespace-nowrap">Invoice Asal</th>
                <th class="text-left py-2 px-2 whitespace-nowrap">Customer Name</th>
                <th class="text-left py-2 px-2 whitespace-nowrap">Kasir</th>
                <th class="text-right py-2 px-2 whitespace-nowrap">Amount (Retur)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ret in report.sale_returns" :key="ret.id" class="border-b hover:bg-amber-50/50">
                <td class="py-2 px-2 font-medium">{{ ret.return_number }}</td>
                <td class="py-2 px-2">{{ formatDateShort(ret.return_date) }}</td>
                <td class="py-2 px-2">{{ ret.sale?.invoice_number || '-' }}</td>
                <td class="py-2 px-2">{{ ret.sale?.customer_name || 'Umum' }}</td>
                <td class="py-2 px-2">{{ ret.user?.name || '-' }}</td>
                <td class="py-2 px-2 text-right font-medium text-red-600">-{{ formatNumber(ret.total, 2) }}</td>
              </tr>
              <tr v-if="!report.sale_returns?.length" class="border-b">
                <td colspan="6" class="py-6 text-center text-gray-500">Tidak ada retur pada periode ini.</td>
              </tr>
            </tbody>
            <tfoot v-if="report.sale_returns?.length">
              <tr class="border-t-2 bg-amber-100 font-bold text-red-700">
                <td class="py-2 px-2" colspan="5">Total RJ POS (Retur)</td>
                <td class="py-2 px-2 text-right">-{{ formatNumber(summary.total_returns, 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <p class="text-xs text-gray-500 mt-4 text-right">-- 1 of 1 --</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatDate, formatPaymentMethod, formatNumber } from '@/utils/format'
import { MagnifyingGlassIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
import { format } from 'date-fns'
import html2pdf from 'html2pdf.js'

const authStore = useAuthStore()
const toast = useToast()
const loading = ref(false)
const pdfLoading = ref(false)
const report = ref(null)
const reportEl = ref(null)
const warehouses = ref([])
const users = ref([])

const today = new Date().toISOString().slice(0, 10)
const filters = ref({
  start_date: today,
  end_date: today,
  warehouse_id: '',
  user_id: ''
})

const currentUserName = computed(() => authStore.user?.name || '-')

const dateRangeFormatted = computed(() => {
  if (!report.value?.start_date) return '-'
  const start = format(new Date(report.value.start_date), 'dd-MM-yyyy')
  const end = report.value.end_date
    ? format(new Date(report.value.end_date), 'dd-MM-yyyy')
    : start
  return start === end ? start : `${start} - ${end}`
})

const printedAt = computed(() => format(new Date(), 'dd/MM/yyyy HH.mm.ss'))

const summary = computed(() => report.value?.summary ?? {
  total_revenue: 0,
  total_returns: 0,
  net_revenue: 0,
  by_payment_method: { cash: 0, card: 0, transfer: 0, other: 0, credit: 0 }
})

function formatDateShort(date) {
  if (!date) return '-'
  return format(new Date(date), 'dd-MM-yyyy')
}

const hasPermission = (p) => authStore.hasPermission(p)

const loadReport = async () => {
  const start = filters.value.start_date
  const end = filters.value.end_date
  if (!start || !end) {
    toast.error('Pilih tanggal mulai dan tanggal akhir')
    return
  }
  if (end < start) {
    toast.error('Tanggal akhir tidak boleh sebelum tanggal mulai')
    return
  }
  loading.value = true
  report.value = null
  try {
    const params = { start_date: start, end_date: end }
    if (filters.value.warehouse_id) params.warehouse_id = filters.value.warehouse_id
    if (filters.value.user_id) params.user_id = filters.value.user_id
    const res = await api.get('/reports/daily-cashier-summary', { params })
    report.value = res.data
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal memuat laporan')
  } finally {
    loading.value = false
  }
}

const exportToPdf = async () => {
  if (!report.value || !reportEl.value) return
  pdfLoading.value = true
  try {
    const filename = `Daily-Cashier-Summary-${report.value.start_date}-${report.value.end_date}.pdf`
    const opt = {
      margin: 10,
      filename,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
    }
    await html2pdf().set(opt).from(reportEl.value).save()
    toast.success('PDF berhasil diunduh')
  } catch (e) {
    console.error(e)
    toast.error('Gagal membuat PDF')
  } finally {
    pdfLoading.value = false
  }
}

const loadWarehouses = async () => {
  try {
    const res = await api.get('/warehouses', { params: { per_page: 100 } })
    warehouses.value = res.data.data ?? res.data
  } catch (e) {
    console.error(e)
  }
}

const loadUsers = async () => {
  try {
    const res = await api.get('/users', { params: { per_page: 100 } })
    users.value = res.data.data ?? res.data
  } catch (e) {
    console.error(e)
  }
}

onMounted(() => {
  loadWarehouses()
  loadUsers()
  loadReport()
})
</script>

<style scoped>
</style>
