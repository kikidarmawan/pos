<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Buat Pembelian dari Supplier</h1>

    <div class="card">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="label">Supplier *</label>
            <VSelect
              v-model="form.supplier_id"
              :options="suppliers"
              :reduce="(s) => s.id"
              label="name"
              placeholder="Pilih Supplier"
              :filterable="true"
              :clearable="false"
              class="vue-select-custom"
              input-class="input"
            />
          </div>

          <div>
            <label class="label">Gudang *</label>
            <VSelect
              v-model="form.warehouse_id"
              :options="warehouses"
              :reduce="(wh) => wh.id"
              label="name"
              placeholder="Pilih Gudang"
              :filterable="true"
              :clearable="false"
              class="vue-select-custom"
              input-class="input"
              @update:model-value="loadRacks"
            />
          </div>

          <div>
            <label class="label">Tanggal Pembelian *</label>
            <input v-model="form.purchase_date" type="date" required class="input" />
          </div>
        </div>

        <!-- Items: dropdown produk di atas, tabel detail di bawah -->
        <div>
          <h3 class="text-lg font-bold mb-3">Detail Produk *</h3>
          <div class="flex flex-wrap gap-3 items-end mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex-1 min-w-[200px]">
              <label class="label text-xs">Cari / pilih produk</label>
              <VSelect
                v-model="selectedProductToAdd"
                :options="products"
                :reduce="(p) => p.id"
                :get-option-label="(p) => p ? `${p.name} (${p.code || '-'})` : ''"
                placeholder="Nama produk / kode / barcode..."
                :filterable="true"
                :clearable="true"
                class="vue-select-custom"
                input-class="input"
                @update:model-value="onProductSelected"
              />
            </div>
            <button type="button" @click="addSelectedProduct" class="btn btn-primary btn-sm shrink-0">
              <PlusIcon class="w-5 h-5 mr-1 inline" />
              Tambah Produk
            </button>
          </div>

          <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-primary-600 text-white">
                  <th class="text-left py-2 px-3 font-medium">Produk</th>
                  <th class="text-left py-2 px-3 font-medium">Satuan *</th>
                  <th class="text-left py-2 px-3 font-medium">Rak</th>
                  <th class="text-left py-2 px-3 font-medium">Qty *</th>
                  <th class="text-left py-2 px-3 font-medium">Harga *</th>
                  <th class="text-left py-2 px-3 font-medium">Subtotal</th>
                  <th class="w-10 py-2 px-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in form.details" :key="index"
                  class="border-t border-gray-200 hover:bg-gray-50">
                  <td class="py-2 px-3 font-medium">{{ getProductName(item.product_id) }}</td>
                  <td class="py-2 px-3">
                    <VSelect
                      v-model="item.unit_id"
                      :options="item.units"
                      :reduce="(u) => u.unit_id"
                      :get-option-label="(u) => u.unit?.name ?? ''"
                      placeholder="Pilih"
                      :filterable="true"
                      :clearable="false"
                      :append-to-body="true"
                      class="vue-select-custom vue-select-compact"
                      input-class="input input-sm py-1"
                    />
                  </td>
                  <td class="py-2 px-3">
                    <VSelect
                      v-model="item.rack_id"
                      :options="racks"
                      :reduce="(r) => r.id"
                      label="name"
                      placeholder="Pilih"
                      :filterable="true"
                      :clearable="true"
                      :append-to-body="true"
                      class="vue-select-custom vue-select-compact"
                      input-class="input input-sm py-1"
                    />
                  </td>
                  <td class="py-2 px-3">
                    <input v-model="item.quantity" @input="calculateItemSubtotal(index)" type="number" step="0.01"
                      required class="input input-sm w-20 py-1" />
                  </td>
                  <td class="py-2 px-3">
                    <input v-model="item.price" @input="calculateItemSubtotal(index)" type="number" step="0.01"
                      required class="input input-sm w-28 py-1" />
                  </td>
                  <td class="py-2 px-3 font-medium">{{ formatCurrency(item.subtotal) }}</td>
                  <td class="py-2 px-2">
                    <button type="button" @click="removeItem(index)" class="p-1.5 text-red-600 hover:bg-red-50 rounded"
                      title="Hapus baris">
                      <TrashIcon class="w-5 h-5" />
                    </button>
                  </td>
                </tr>
                <tr v-if="form.details.length === 0">
                  <td colspan="7" class="py-8 text-center text-gray-500">
                    Pilih produk di atas lalu klik &quot;Tambah Produk&quot; untuk menambah baris.
                  </td>
                </tr>
              </tbody>
            </table>
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
const selectedProductToAdd = ref(null)

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

const getProductName = (productId) => {
  const p = products.value.find((x) => x.id == productId)
  return p ? p.name : '-'
}

const addProductToTable = (productId) => {
  const existingIndex = form.value.details.findIndex((d) => d.product_id == productId)
  if (existingIndex >= 0) {
    const item = form.value.details[existingIndex]
    item.quantity = (item.quantity || 0) + 1
    item.subtotal = (item.quantity || 0) * (item.price || 0)
    return
  }
  const product = products.value.find((p) => p.id == productId)
  if (!product) return
  const units = product.product_units || product.productUnits || []
  const hargaModal = Number(product.base_price) || 0
  form.value.details.push({
    product_id: productId,
    unit_id: '',
    rack_id: '',
    quantity: 1,
    price: hargaModal,
    subtotal: hargaModal,
    units
  })
}

const onProductSelected = (productId) => {
  if (!productId) return
  addProductToTable(productId)
  selectedProductToAdd.value = null
}

const addSelectedProduct = () => {
  const id = selectedProductToAdd.value
  if (!id) {
    toast.warning('Pilih produk dulu dari dropdown di atas')
    return
  }
  addProductToTable(id)
  selectedProductToAdd.value = null
}

const removeItem = (index) => {
  form.value.details.splice(index, 1)
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
})
</script>
