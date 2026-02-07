<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Buat Pembelian dari Supplier</h1>

    <div class="card">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="label">Supplier *</label>
            <select v-model="form.supplier_id" required class="input">
              <option value="">Pilih Supplier</option>
              <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">
                {{ sup.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="label">Gudang *</label>
            <select v-model="form.warehouse_id" required class="input" @change="loadRacks">
              <option value="">Pilih Gudang</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                {{ wh.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="label">Tanggal Pembelian *</label>
            <input v-model="form.purchase_date" type="date" required class="input" />
          </div>
        </div>

        <!-- Items -->
        <div>
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Detail Produk *</h3>
            <button type="button" @click="addItem" class="btn btn-primary btn-sm">
              <PlusIcon class="w-4 h-4 mr-1" />
              Tambah Produk
            </button>
          </div>

          <div class="space-y-3">
            <div v-for="(item, index) in form.details" :key="index"
              class="grid grid-cols-1 md:grid-cols-6 gap-3 p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="label text-xs">Produk *</label>
                <select v-model="item.product_id" required class="input" @change="loadUnits(index)">
                  <option value="">Pilih</option>
                  <option v-for="prod in products" :key="prod.id" :value="prod.id">
                    {{ prod.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="label text-xs">Satuan *</label>
                <select v-model="item.unit_id" required class="input">
                  <option value="">Pilih</option>
                  <option v-for="unit in item.units" :key="unit.id" :value="unit.id">
                    {{ unit.unit.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="label text-xs">Rak</label>
                <select v-model="item.rack_id" class="input">
                  <option value="">Pilih</option>
                  <option v-for="rack in racks" :key="rack.id" :value="rack.id">
                    {{ rack.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="label text-xs">Qty *</label>
                <input v-model="item.quantity" @input="calculateItemSubtotal(index)" type="number" step="0.01" required
                  class="input" />
              </div>

              <div>
                <label class="label text-xs">Harga *</label>
                <input v-model="item.price" @input="calculateItemSubtotal(index)" type="number" step="0.01" required
                  class="input" />
              </div>

              <div class="flex items-end gap-2">
                <div class="flex-1">
                  <label class="label text-xs">Subtotal</label>
                  <input :value="formatCurrency(item.subtotal)" disabled class="input" />
                </div>
                <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-800 pb-2">
                  <TrashIcon class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div class="border-t pt-6">
          <div class="max-w-md ml-auto space-y-2">
            <div class="flex justify-between">
              <span>Subtotal:</span>
              <span class="font-medium">{{ formatCurrency(subtotal) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span>Pajak:</span>
              <input v-model="form.tax" @input="calculateTotal" type="number" step="1000"
                class="w-32 text-right border rounded px-2 py-1" />
            </div>
            <div class="flex justify-between items-center">
              <span>Diskon:</span>
              <input v-model="form.discount" @input="calculateTotal" type="number" step="1000"
                class="w-32 text-right border rounded px-2 py-1" />
            </div>
            <div class="flex justify-between items-center">
              <span>Ongkir:</span>
              <input v-model="form.shipping_cost" @input="calculateTotal" type="number" step="1000"
                class="w-32 text-right border rounded px-2 py-1" />
            </div>
            <div class="flex justify-between text-lg font-bold pt-2 border-t">
              <span>Total:</span>
              <span class="text-primary-600">{{ formatCurrency(total) }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
          <router-link to="/purchases" class="btn btn-secondary">
            Batal
          </router-link>
          <button type="submit" :disabled="loading" class="btn btn-success">
            {{ loading ? 'Menyimpan...' : 'Simpan Pembelian' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency } from '@/utils/format'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const toast = useToast()

const loading = ref(false)
const suppliers = ref([])
const warehouses = ref([])
const products = ref([])
const racks = ref([])

const form = ref({
  supplier_id: '',
  warehouse_id: '',
  purchase_date: new Date().toISOString().split('T')[0],
  tax: 0,
  discount: 0,
  shipping_cost: 0,
  notes: '',
  details: []
})

const subtotal = computed(() => {
  return form.value.details.reduce((sum, item) => sum + (item.subtotal || 0), 0)
})

const total = computed(() => {
  return subtotal.value + (form.value.tax || 0) + (form.value.shipping_cost || 0) - (form.value.discount || 0)
})

const addItem = () => {
  form.value.details.push({
    product_id: '',
    unit_id: '',
    rack_id: '',
    quantity: 1,
    price: 0,
    subtotal: 0,
    units: []
  })
}

const removeItem = (index) => {
  form.value.details.splice(index, 1)
}

const loadUnits = async (index) => {
  const item = form.value.details[index]
  const product = products.value.find(p => p.id == item.product_id)
  if (product) {
    item.units = product.product_units || []
  }
}

const calculateItemSubtotal = (index) => {
  const item = form.value.details[index]
  item.subtotal = (item.quantity || 0) * (item.price || 0)
  calculateTotal()
}

const calculateTotal = () => {
  // Computed properties will auto-update
}

const loadRacks = async () => {
  if (!form.value.warehouse_id) return

  try {
    const response = await api.get('/racks', {
      params: { warehouse_id: form.value.warehouse_id, per_page: 100 }
    })
    racks.value = response.data.data
  } catch (error) {
    console.error('Error loading racks:', error)
  }
}

const loadSuppliers = async () => {
  try {
    const response = await api.get('/suppliers', { params: { per_page: 100, is_active: 1 } })
    suppliers.value = response.data.data
  } catch (error) {
    toast.error('Gagal memuat supplier')
  }
}

const loadWarehouses = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 100, is_active: 1 } })
    warehouses.value = response.data.data
  } catch (error) {
    toast.error('Gagal memuat gudang')
  }
}

const loadProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 1000, is_active: 1 } })
    products.value = response.data.data
  } catch (error) {
    toast.error('Gagal memuat produk')
  }
}

const handleSubmit = async () => {
  if (form.value.details.length === 0) {
    toast.error('Tambahkan minimal 1 produk')
    return
  }

  loading.value = true

  try {
    const payload = {
      ...form.value,
      subtotal: subtotal.value,
      total: total.value
    }

    await api.post('/purchases', payload)
    toast.success('Pembelian berhasil disimpan')
    router.push('/purchases')
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menyimpan pembelian'
    toast.error(message)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadSuppliers()
  loadWarehouses()
  loadProducts()
  addItem()
})
</script>
