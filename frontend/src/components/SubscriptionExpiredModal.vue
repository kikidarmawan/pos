<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60">
      <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-start gap-4">
          <div>
            <h2 class="text-xl font-bold text-gray-900">
              {{ transferInfo ? 'Transfer Pembayaran' : 'Langganan Berakhir' }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
              <template v-if="transferInfo">
                Transfer ke rekening berikut, lalu hubungi kami untuk konfirmasi.
              </template>
              <template v-else>
                Untuk melanjutkan menggunakan aplikasi, silakan perpanjang langganan dengan memilih paket di bawah dan
                melakukan transfer.
              </template>
            </p>
          </div>
          <button type="button" @click="transferInfo ? (transferInfo = null) : subscriptionStore.closePayModal()"
            class="text-gray-400 hover:text-gray-600 p-1" title="Tutup">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Tampilan rekening setelah klik Bayar -->
        <div v-if="transferInfo" class="p-6 space-y-4">
          <p class="text-lg font-bold text-primary-600">
            Jumlah transfer: {{ formatCurrency(transferInfo.amount) }}
          </p>
          <p class="text-sm text-gray-600">{{ transferInfo.package_name }}</p>
          <div class="space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <p class="text-sm font-semibold text-gray-700">Rekening tujuan:</p>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between gap-2">
                <span class="text-gray-600">BCA</span>
                <span class="font-mono font-semibold">4310366141</span>
              </div>
              <div class="flex justify-between gap-2">
                <span class="text-gray-600">Sea Bank</span>
                <span class="font-mono font-semibold">901250504500</span>
              </div>
              <p class="text-gray-600 pt-1">a.n. <strong>Kiki Darmawan</strong></p>
            </div>
          </div>
          <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-semibold">Jika sudah membayar, harap mengirimkan bukti pembayaran ke nomor whatsapp <a
                :href="'tel:' + transferInfo.confirm_phone" class="underline font-bold">{{ transferInfo.confirm_phone
                }}</a>
              untuk konfirmasi pembayaran.</p>
          </div>
          <button type="button" @click="transferInfo = null" class="btn btn-secondary w-full">
            Kembali ke pilihan paket
          </button>
        </div>

        <!-- Daftar paket -->
        <div v-else class="p-6 space-y-4">
          <div v-if="loadingPackages" class="text-center py-8">
            <div
              class="inline-block animate-spin rounded-full h-8 w-8 border-2 border-primary-600 border-t-transparent">
            </div>
            <p class="mt-2 text-sm text-gray-500">Memuat paket...</p>
          </div>
          <template v-else>
            <div v-for="pkg in packages" :key="pkg.id"
              class="border rounded-lg p-4 hover:border-primary-500 transition-colors">
              <div class="flex justify-between items-start gap-4">
                <div class="min-w-0 flex-1">
                  <h3 class="font-semibold text-gray-900">{{ pkg.name }}</h3>
                  <p class="text-sm text-gray-500 mt-0.5">{{ pkg.description }}</p>
                  <p class="text-lg font-bold text-primary-600 mt-2">
                    {{ formatCurrency(pkg.price) }}
                    <span class="text-sm font-normal text-gray-500">/ {{ pkg.duration_days }} hari</span>
                  </p>
                </div>
                <button type="button" :disabled="payingId === pkg.id" @click="pay(pkg)"
                  class="btn btn-primary shrink-0">
                  {{ payingId === pkg.id ? 'Memproses...' : 'Bayar' }}
                </button>
              </div>
            </div>
            <p v-if="packages.length === 0" class="text-center text-gray-500 py-4">Belum ada paket tersedia.</p>
          </template>
        </div>
        <div v-if="!transferInfo" class="p-6 border-t bg-gray-50 rounded-b-xl">
          <p class="text-xs text-gray-500 text-center">
            Pembayaran via transfer ke BCA atau Sea Bank. Setelah transfer, hubungi kami untuk konfirmasi agar langganan
            dapat
            diaktifkan.
          </p>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useSubscriptionStore } from '@/stores/subscription'
import api from '@/utils/axios'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: { type: Boolean, default: false }
})

const subscriptionStore = useSubscriptionStore()
const packages = ref([])
const loadingPackages = ref(false)
const payingId = ref(null)
const transferInfo = ref(null)

function formatCurrency(n) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(Number(n))
}

async function loadPackages() {
  loadingPackages.value = true
  try {
    const res = await api.get('/packages')
    packages.value = res.data || []
  } catch (_) {
    packages.value = []
  } finally {
    loadingPackages.value = false
  }
}

async function pay(pkg) {
  payingId.value = pkg.id
  try {
    const res = await api.post('/subscriptions', { package_id: pkg.id })
    transferInfo.value = {
      amount: res.data.amount ?? pkg.price,
      package_name: res.data.package_name ?? pkg.name,
      confirm_phone: res.data.confirm_phone ?? '0815 7111 413',
    }
  } catch (e) {
    console.error(e)
    alert(e.response?.data?.message || e.message || 'Gagal memproses.')
  } finally {
    payingId.value = null
  }
}

watch(() => props.show, (visible) => {
  if (visible) {
    loadPackages()
    transferInfo.value = null
  }
})
</script>
