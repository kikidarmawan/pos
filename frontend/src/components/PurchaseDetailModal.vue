<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[92vh] overflow-hidden flex flex-col">
      <div class="p-4 border-b flex justify-between items-center shrink-0">
        <h2 class="text-xl font-bold">Detail Pembelian</h2>
        <button type="button" @click="emit('close')" class="p-2 text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div class="p-4 overflow-y-auto flex-1">
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-10 w-10 border-2 border-primary-600 border-t-transparent"></div>
          <p class="mt-2 text-gray-500">Memuat detail...</p>
        </div>

        <div v-else-if="purchase" class="space-y-4">
          <!-- Header -->
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="text-gray-500">Invoice:</span>
              <span class="font-medium ml-2">{{ purchase.invoice_number }}</span>
            </div>
            <div>
              <span class="text-gray-500">Tanggal:</span>
              <span class="ml-2">{{ formatDate(purchase.purchase_date) }}</span>
            </div>
            <div>
              <span class="text-gray-500">Supplier:</span>
              <span class="ml-2">{{ purchase.supplier?.name || '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500">Gudang:</span>
              <span class="ml-2">{{ purchase.warehouse?.name || '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500">User:</span>
              <span class="ml-2">{{ purchase.user?.name || '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500">Status:</span>
              <span :class="['ml-2 badge', statusBadgeClass]">
                {{ statusLabel }}
              </span>
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
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">Dapat diretur</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(d, i) in (purchase.details || [])" :key="i">
                    <td>{{ d.product?.name || '-' }}</td>
                    <td>{{ d.unit?.name || '-' }}</td>
                    <td class="text-right">{{ formatStock(d.quantity) }}</td>
                    <td class="text-right">{{ formatCurrency(d.price) }}</td>
                    <td class="text-right font-medium">{{ formatCurrency(d.subtotal) }}</td>
                    <td class="text-right text-gray-600">{{ formatStock(d.returnable_quantity ?? 0) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Data Retur (jika ada) -->
          <div v-if="purchase.purchase_returns?.length" class="border-t pt-4">
            <h3 class="font-medium text-gray-700 mb-3">Data Retur Pembelian</h3>
            <p class="text-sm text-gray-500 mb-3">Pembelian ini memiliki {{ purchase.purchase_returns.length }} retur.</p>
            <div v-for="ret in purchase.purchase_returns" :key="ret.id" class="mb-4 p-4 bg-amber-50 rounded-lg border border-amber-200">
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
              <span>{{ formatCurrency(purchase.subtotal) }}</span>
            </div>
            <div v-if="(purchase.discount || 0) > 0" class="flex justify-between">
              <span class="text-gray-500">Diskon</span>
              <span>-{{ formatCurrency(purchase.discount) }}</span>
            </div>
            <div v-if="(purchase.tax || 0) > 0" class="flex justify-between">
              <span class="text-gray-500">Pajak</span>
              <span>{{ formatCurrency(purchase.tax) }}</span>
            </div>
            <div v-if="(purchase.shipping_cost || 0) > 0" class="flex justify-between">
              <span class="text-gray-500">Ongkir</span>
              <span>{{ formatCurrency(purchase.shipping_cost) }}</span>
            </div>
            <div class="flex justify-between font-bold text-lg pt-2">
              <span>Total</span>
              <span class="text-primary-600">{{ formatCurrency(purchase.total) }}</span>
            </div>
          </div>

          <div v-if="purchase.notes" class="border-t pt-4">
            <span class="text-gray-500 text-sm">Catatan: </span>
            <span class="text-sm">{{ purchase.notes }}</span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="p-4 border-t flex gap-3 shrink-0">
        <router-link
          v-if="purchase && purchase.status === 'received' && hasPermission('create_purchases')"
          :to="'/purchase-returns/create?invoice=' + encodeURIComponent(purchase.invoice_number)"
          class="btn btn-primary flex-1"
        >
          Buat Retur Pembelian
        </router-link>
        <button type="button" @click="emit('close')" class="btn btn-secondary flex-1">
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatDate, formatStock } from '@/utils/format'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  purchaseId: { type: [Number, String], default: null }
})

const emit = defineEmits(['close', 'updated'])

const authStore = useAuthStore()
const toast = useToast()
const loading = ref(false)
const purchase = ref(null)

const hasPermission = (permission) => authStore.hasPermission(permission)

const statusLabel = computed(() => {
  if (!purchase.value) return ''
  const s = purchase.value.status
  if (s === 'received') return 'Diterima'
  if (s === 'pending') return 'Pending'
  if (s === 'cancelled') return 'Dibatalkan'
  return s
})

const statusBadgeClass = computed(() => {
  if (!purchase.value) return ''
  const s = purchase.value.status
  if (s === 'received') return 'badge-success'
  if (s === 'pending') return 'badge-warning'
  if (s === 'cancelled') return 'badge-danger'
  return ''
})

const fetchPurchase = async () => {
  if (!props.purchaseId) return
  loading.value = true
  purchase.value = null
  try {
    const res = await api.get(`/purchases/${props.purchaseId}`)
    purchase.value = res.data
  } catch (e) {
    toast.error('Gagal memuat detail pembelian')
  } finally {
    loading.value = false
  }
}

watch(() => props.purchaseId, (id) => {
  if (id) fetchPurchase()
}, { immediate: true })
</script>
