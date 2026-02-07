<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Pembelian dari Supplier</h1>
      <router-link v-if="hasPermission('create_purchases')" to="/purchases/create" class="btn btn-primary">
        <PlusIcon class="w-5 h-5 mr-2" />
        Buat Pembelian
      </router-link>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input v-model="filters.search" @input="loadPurchases" type="text" placeholder="Cari invoice..."
          class="input" />
        <select v-model="filters.supplier_id" @change="loadPurchases" class="input">
          <option value="">Semua Supplier</option>
          <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">
            {{ sup.name }}
          </option>
        </select>
        <select v-model="filters.status" @change="loadPurchases" class="input">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="received">Diterima</option>
          <option value="cancelled">Dibatalkan</option>
        </select>
        <input v-model="filters.start_date" @change="loadPurchases" type="date" class="input" />
      </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap">Invoice</th>
              <th class="whitespace-nowrap">Tanggal</th>
              <th class="whitespace-nowrap">Supplier</th>
              <th class="whitespace-nowrap">Gudang</th>
              <th class="whitespace-nowrap">Total</th>
              <th class="whitespace-nowrap">Status</th>
              <th class="whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="purchase in purchases.data" :key="purchase.id">
              <td class="font-medium">{{ purchase.invoice_number }}</td>
              <td>{{ formatDate(purchase.purchase_date) }}</td>
              <td>{{ purchase.supplier?.name }}</td>
              <td>{{ purchase.warehouse?.name }}</td>
              <td>{{ formatCurrency(purchase.total) }}</td>
              <td>
                <span :class="{
                  'badge badge-success': purchase.status === 'received',
                  'badge badge-warning': purchase.status === 'pending',
                  'badge badge-danger': purchase.status === 'cancelled'
                }">
                  {{ purchase.status }}
                </span>
              </td>
              <td>
                <button v-if="purchase.status === 'received' && hasPermission('cancel_purchases')"
                  @click="cancelPurchase(purchase)" class="text-red-600 hover:text-red-800">
                  Batalkan
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatDate } from '@/utils/format'
import { PlusIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toast = useToast()

const purchases = ref({ data: [] })
const suppliers = ref([])

const filters = ref({
  search: '',
  supplier_id: '',
  status: '',
  start_date: ''
})

const hasPermission = (permission) => authStore.hasPermission(permission)

const loadPurchases = async () => {
  try {
    const response = await api.get('/purchases', { params: filters.value })
    purchases.value = response.data
  } catch (error) {
    toast.error('Gagal memuat data pembelian')
  }
}

const loadSuppliers = async () => {
  try {
    const response = await api.get('/suppliers', { params: { per_page: 100 } })
    suppliers.value = response.data.data
  } catch (error) {
    console.error('Error loading suppliers:', error)
  }
}

const cancelPurchase = async (purchase) => {
  if (!confirm(`Yakin ingin membatalkan pembelian ${purchase.invoice_number}?`)) return

  try {
    await api.post(`/purchases/${purchase.id}/cancel`)
    toast.success('Pembelian berhasil dibatalkan')
    loadPurchases()
  } catch (error) {
    toast.error('Gagal membatalkan pembelian')
  }
}

onMounted(() => {
  loadPurchases()
  loadSuppliers()
})
</script>
