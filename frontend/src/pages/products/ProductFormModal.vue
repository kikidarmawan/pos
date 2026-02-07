<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
        <h2 class="text-2xl font-bold">{{ product ? 'Edit Produk' : 'Tambah Produk' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">Kategori *</label>
            <select v-model="form.category_id" required class="input">
              <option value="">Pilih Kategori</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="label">Kode Produk *</label>
            <input v-model="form.code" type="text" required class="input" />
          </div>

          <div>
            <label class="label">Nama Produk *</label>
            <input v-model="form.name" type="text" required class="input" />
          </div>

          <div>
            <label class="label">Barcode</label>
            <input v-model="form.barcode" type="text" class="input" />
          </div>

          <div class="md:col-span-2">
            <label class="label">Deskripsi</label>
            <textarea v-model="form.description" rows="3" class="input"></textarea>
          </div>

          <div>
            <label class="label">
              Satuan Dasar *
              <span class="text-xs text-gray-500 font-normal ml-1">(untuk konversi stok)</span>
            </label>
            <select v-model="form.base_unit_id" required class="input">
              <option value="">Pilih Satuan</option>
              <option v-for="unit in units" :key="unit.id" :value="unit.id">
                {{ unit.name }} ({{ unit.code }})
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">
              💡 Satuan terkecil untuk menghitung stok. Contoh: Kabel → satuan dasar "Meter"
            </p>
          </div>

          <div>
            <label class="label">
              Harga Modal *
              <span class="text-xs text-gray-500 font-normal ml-1">(harga beli/pokok per satuan dasar)</span>
            </label>
            <input v-model="form.base_price" type="number" step="0.01" required class="input"
              placeholder="Contoh: 4000" />
          </div>

          <div>
            <label class="label">Minimum Stok</label>
            <input v-model="form.minimum_stock" type="number" step="0.01" class="input" />
          </div>

          <div>
            <label class="label">Status</label>
            <select v-model.number="form.is_active" class="input">
              <option :value="1">Aktif</option>
              <option :value="0">Tidak Aktif</option>
            </select>
          </div>
        </div>

        <!-- Multi-Unit Section -->
        <div class="border-t pt-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Multi Satuan * (Fitur Unggulan)</h3>
            <button type="button" @click="addUnit" class="btn btn-primary btn-sm">
              <PlusIcon class="w-4 h-4 mr-1" />
              Tambah Satuan
            </button>
          </div>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <p class="text-sm text-blue-800">
              <strong>💡 Contoh:</strong> Produk "Kabel Listrik"<br />
              • <strong>Satuan Dasar:</strong> Meter | <strong>Harga Modal:</strong> Rp 4.000/meter<br />
              • <strong>Satuan Jual 1:</strong> Roll (konversi: 100 meter) | Harga Jual: Rp 500.000/Roll<br />
              • <strong>Satuan Jual 2:</strong> Meter (konversi: 1 meter) | Harga Jual: Rp 5.500/Meter<br />
              → Stok dihitung otomatis dalam Meter (satuan dasar)
            </p>
          </div>

          <div class="space-y-3">
            <div v-for="(unit, index) in form.units" :key="index"
              class="grid grid-cols-1 md:grid-cols-5 gap-3 p-4 bg-gray-50 rounded-lg">
              <div>
                <label class="label text-xs">Satuan *</label>
                <select v-model="unit.unit_id" required class="input">
                  <option value="">Pilih</option>
                  <option v-for="u in units" :key="u.id" :value="u.id">
                    {{ u.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="label text-xs">Faktor Konversi *</label>
                <input v-model="unit.conversion_factor" type="number" step="0.001" required class="input"
                  placeholder="1" title="Contoh: 1 Roll = 100 Meter, maka faktor konversi = 100" />
              </div>

              <div>
                <label class="label text-xs">Harga Jual *</label>
                <input v-model="unit.selling_price" type="number" step="0.01" required class="input" />
              </div>

              <div>
                <label class="label text-xs">Barcode</label>
                <input v-model="unit.barcode" type="text" class="input" />
              </div>

              <div class="flex items-end gap-2">
                <label class="flex items-center">
                  <input v-model="unit.is_default" type="checkbox" class="mr-2" @change="setDefaultUnit(index)" />
                  <span class="text-xs">Default</span>
                </label>
                <button type="button" @click="removeUnit(index)" class="text-red-600 hover:text-red-800">
                  <TrashIcon class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-6 border-t">
          <button type="button" @click="$emit('close')" class="btn btn-secondary">
            Batal
          </button>
          <button type="submit" :disabled="loading" class="btn btn-primary">
            {{ loading ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { XMarkIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  product: Object,
  categories: Array,
  units: Array
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const loading = ref(false)

const form = ref({
  category_id: '',
  code: '',
  name: '',
  barcode: '',
  description: '',
  base_unit_id: '',
  base_price: 0,
  minimum_stock: 0,
  is_active: 1,
  units: []
})

const addUnit = () => {
  form.value.units.push({
    unit_id: '',
    conversion_factor: 1,
    selling_price: 0,
    barcode: '',
    is_default: form.value.units.length === 0
  })
}

const removeUnit = (index) => {
  form.value.units.splice(index, 1)
}

const setDefaultUnit = (index) => {
  form.value.units.forEach((unit, i) => {
    unit.is_default = i === index
  })
}

const handleSubmit = async () => {
  if (form.value.units.length === 0) {
    toast.error('Minimal 1 satuan harus ditambahkan')
    return
  }

  loading.value = true

  try {
    // Prepare data as JSON
    const payload = {
      category_id: form.value.category_id,
      code: form.value.code,
      name: form.value.name,
      barcode: form.value.barcode || null,
      description: form.value.description || null,
      base_unit_id: form.value.base_unit_id,
      base_price: parseFloat(form.value.base_price),
      minimum_stock: parseFloat(form.value.minimum_stock) || 0,
      is_active: form.value.is_active === 1 || form.value.is_active === true,
      units: form.value.units.map(u => ({
        unit_id: u.unit_id,
        conversion_factor: parseFloat(u.conversion_factor),
        selling_price: parseFloat(u.selling_price),
        barcode: u.barcode || null,
        is_default: u.is_default === 1 || u.is_default === true
      }))
    }

    if (props.product) {
      await api.put(`/products/${props.product.id}`, payload)
      toast.success('Produk berhasil diperbarui')
    } else {
      await api.post('/products', payload)
      toast.success('Produk berhasil ditambahkan')
    }

    emit('saved')
  } catch (error) {
    console.error('Error submitting product:', error)
    const message = error.response?.data?.message || 'Terjadi kesalahan'

    // Show validation errors if any
    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat()
      toast.error(errors.join(', '))
    } else {
      toast.error(message)
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (props.product) {
    form.value = {
      category_id: props.product.category_id,
      code: props.product.code,
      name: props.product.name,
      barcode: props.product.barcode || '',
      description: props.product.description || '',
      base_unit_id: props.product.base_unit_id,
      base_price: props.product.base_price,
      minimum_stock: props.product.minimum_stock,
      is_active: props.product.is_active ? 1 : 0,
      units: props.product.product_units?.map(pu => ({
        unit_id: pu.unit_id,
        conversion_factor: pu.conversion_factor,
        selling_price: pu.selling_price,
        barcode: pu.barcode || '',
        is_default: pu.is_default
      })) || []
    }
  } else {
    addUnit() // Add first unit by default
  }
})
</script>
