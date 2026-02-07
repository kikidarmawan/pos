<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
        <h2 class="text-xl font-bold">Input Stok Manual</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <!-- Produk -->
        <div>
          <label class="label">Produk *</label>
          <select v-model="form.product_id" required class="input" @change="onProductChange">
            <option value="">Pilih produk</option>
            <option v-for="p in products" :key="p.id" :value="p.id">
              {{ p.name }} ({{ p.code }})
            </option>
          </select>
          <p v-if="selectedProduct" class="text-xs text-gray-500 mt-1">
            Satuan dasar: {{ selectedProduct.base_unit?.name || selectedProduct.baseUnit?.name }}
          </p>
        </div>

        <!-- Satuan -->
        <div>
          <label class="label">Satuan *</label>
          <select v-model="form.unit_id" required class="input">
            <option value="">Pilih satuan</option>
            <option v-if="baseUnit" :value="baseUnit.id">
              {{ baseUnit.name }} (satuan dasar)
            </option>
            <template v-for="pu in productUnits" :key="pu.id">
              <option :value="pu.unit_id || pu.unit?.id">
                {{ pu.unit?.name }} (1 = {{ pu.conversion_factor }} {{ baseUnit?.name }})
              </option>
            </template>
          </select>
          <p class="text-xs text-gray-500 mt-1">
            Pilih satuan untuk input. Contoh: kabel dengan Meter & Roll, pilih Roll lalu isi 2 = 200 Meter
          </p>
        </div>

        <!-- Jumlah & Tipe -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Jumlah *</label>
            <input v-model.number="form.quantity" type="number" step="0.01" min="0.01" required
              class="input" placeholder="0" />
          </div>
          <div>
            <label class="label">Tipe *</label>
            <select v-model="form.type" required class="input">
              <option value="in">Stok Masuk (+)</option>
              <option value="out">Stok Keluar (-)</option>
            </select>
          </div>
        </div>

        <!-- Gudang & Rak -->
        <div>
          <label class="label">Gudang *</label>
          <select v-model="form.warehouse_id" required class="input" @change="loadRacks">
            <option value="">Pilih gudang</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
              {{ wh.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="label">Rak (opsional)</label>
          <select v-model="form.rack_id" class="input">
            <option value="">Tanpa rak</option>
            <option v-for="r in racks" :key="r.id" :value="r.id">
              {{ r.name }}
            </option>
          </select>
        </div>

        <!-- Catatan -->
        <div>
          <label class="label">Catatan</label>
          <textarea v-model="form.notes" rows="2" class="input"
            placeholder="Contoh: Stock opname, retur supplier, dll"></textarea>
        </div>

        <!-- Preview konversi -->
        <div v-if="quantityInBaseUnit != null && form.quantity > 0" class="p-3 bg-blue-50 rounded-lg text-sm">
          <span class="font-medium">Konversi:</span> {{ form.quantity }} {{ selectedUnitName }} =
          {{ quantityInBaseUnit }} {{ baseUnit?.name }} (satuan dasar)
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="$emit('close')" class="btn btn-secondary">
            Batal
          </button>
          <button type="submit" class="btn btn-primary" :disabled="submitting">
            <span v-if="submitting">Memproses...</span>
            <span v-else>Simpan</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import api from '@/utils/axios'

const toast = useToast()

const props = defineProps({
  product: { type: Object, default: null },
  warehouseId: { type: [Number, String], default: null }
})

const emit = defineEmits(['close', 'saved'])

const products = ref([])
const warehouses = ref([])
const racks = ref([])
const submitting = ref(false)

const form = ref({
  product_id: '',
  unit_id: '',
  quantity: '',
  warehouse_id: '',
  rack_id: '',
  type: 'in',
  notes: ''
})

const selectedProduct = computed(() => {
  if (props.product) return props.product
  return products.value.find((p) => p.id == form.value.product_id) || null
})

const baseUnit = computed(() => {
  const p = selectedProduct.value
  return p?.base_unit || p?.baseUnit || null
})

const productUnits = computed(() => {
  const p = selectedProduct.value
  const pus = p?.product_units || p?.productUnits || []
  return pus
})

const selectedUnitName = computed(() => {
  if (form.value.unit_id == baseUnit.value?.id) return baseUnit.value?.name || ''
  const pu = productUnits.value.find((u) => (u.unit_id || u.unit?.id) == form.value.unit_id)
  return pu?.unit?.name || ''
})

const quantityInBaseUnit = computed(() => {
  const p = selectedProduct.value
  const qty = parseFloat(form.value.quantity)
  if (!p || !qty || qty <= 0) return null

  if (form.value.unit_id == baseUnit.value?.id) return qty

  const pu = productUnits.value.find((u) => (u.unit_id || u.unit?.id) == form.value.unit_id)
  if (!pu) return null

  return qty * (parseFloat(pu.conversion_factor) || 1)
})

const onProductChange = () => {
  form.value.unit_id = baseUnit.value?.id || ''
}

const loadProducts = async () => {
  try {
    const res = await api.get('/products', { params: { per_page: 200, is_active: 1 } })
    products.value = res.data.data || []
  } catch (e) {
    console.error('Failed to load products', e)
  }
}

const loadWarehouses = async () => {
  try {
    const res = await api.get('/warehouses', { params: { per_page: 100 } })
    warehouses.value = res.data.data || []
  } catch (e) {
    console.error('Failed to load warehouses', e)
  }
}

const loadRacks = async () => {
  if (!form.value.warehouse_id) {
    racks.value = []
    return
  }
  try {
    const res = await api.get('/racks', { params: { warehouse_id: form.value.warehouse_id } })
    racks.value = res.data.data || []
  } catch (e) {
    racks.value = []
  }
}

const handleSubmit = async () => {
  submitting.value = true
  try {
    await api.post('/stocks/adjustment', {
      product_id: form.value.product_id,
      warehouse_id: form.value.warehouse_id,
      rack_id: form.value.rack_id || null,
      quantity: Math.abs(parseFloat(form.value.quantity)),
      unit_id: form.value.unit_id || null,
      type: form.value.type,
      notes: form.value.notes || 'Input stok manual'
    })
    emit('saved')
    emit('close')
  } catch (e) {
    const msg = e.response?.data?.message || e.message || 'Gagal menyimpan'
    toast.error(msg)
  } finally {
    submitting.value = false
  }
}

watch(() => props.product, (p) => {
  if (p) {
    form.value.product_id = p.id
    form.value.unit_id = p.base_unit?.id || p.baseUnit?.id || ''
  }
}, { immediate: true })

watch(() => props.warehouseId, (id) => {
  if (id) form.value.warehouse_id = id
}, { immediate: true })

onMounted(async () => {
  await loadProducts()
  await loadWarehouses()
  if (props.product) {
    const exists = products.value.some((p) => p.id === props.product.id)
    if (!exists) products.value = [props.product, ...products.value]
    form.value.product_id = props.product.id
    form.value.unit_id = props.product.base_unit?.id || props.product.baseUnit?.id || ''
  }
  if (props.warehouseId) form.value.warehouse_id = props.warehouseId
  if (form.value.warehouse_id) loadRacks()
})
</script>
