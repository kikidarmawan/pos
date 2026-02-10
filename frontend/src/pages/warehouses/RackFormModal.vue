<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">{{ rack ? 'Edit Rak' : 'Tambah Rak Baru' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div v-if="rack">
          <label class="label">Gudang</label>
          <input :value="warehouseName" type="text" class="input bg-gray-100" readonly disabled />
        </div>
        <div v-else>
          <label class="label">Gudang *</label>
          <select v-model="form.warehouse_id" required class="input">
            <option value="">Pilih Gudang</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }} ({{ wh.code }})</option>
          </select>
        </div>
        <div>
          <label class="label">Kode *</label>
          <input v-model="form.code" type="text" required class="input" placeholder="Contoh: A-01" />
        </div>
        <div>
          <label class="label">Nama *</label>
          <input v-model="form.name" type="text" required class="input" placeholder="Contoh: Rak A1" />
        </div>
        <div>
          <label class="label">Deskripsi</label>
          <textarea v-model="form.description" rows="2" class="input" placeholder="Deskripsi rak (opsional)"></textarea>
        </div>
        <div class="flex gap-3 pt-4">
          <button type="button" @click="$emit('close')" class="flex-1 btn btn-secondary">Batal</button>
          <button type="submit" class="flex-1 btn btn-primary" :disabled="submitting">
            {{ submitting ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import api from '@/utils/axios'

const props = defineProps({
  rack: { type: Object, default: null },
  /** Gudang yang dipilih (untuk mode tambah, pre-fill warehouse_id) */
  selectedWarehouse: { type: Object, default: null }
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const submitting = ref(false)
const warehouses = ref([])

const form = reactive({
  warehouse_id: '',
  code: '',
  name: '',
  description: ''
})

const warehouseName = computed(() => {
  if (props.rack?.warehouse) return `${props.rack.warehouse.name} (${props.rack.warehouse.code})`
  return props.selectedWarehouse ? `${props.selectedWarehouse.name} (${props.selectedWarehouse.code})` : '-'
})

watch(() => props.rack, (r) => {
  if (r) {
    form.warehouse_id = r.warehouse_id
    form.code = r.code || ''
    form.name = r.name || ''
    form.description = r.description || ''
  } else {
    form.warehouse_id = props.selectedWarehouse?.id ?? ''
    form.code = ''
    form.name = ''
    form.description = ''
  }
}, { immediate: true })

watch(() => props.selectedWarehouse?.id, (id) => {
  if (!props.rack && id) form.warehouse_id = id
}, { immediate: true })

const loadWarehouses = async () => {
  try {
    const res = await api.get('/warehouses', { params: { per_page: 100 } })
    warehouses.value = res.data.data ?? res.data
  } catch (e) {
    toast.error('Gagal memuat daftar gudang')
  }
}

const handleSubmit = async () => {
  submitting.value = true
  try {
    const payload = {
      warehouse_id: Number(form.warehouse_id),
      code: form.code.trim(),
      name: form.name.trim(),
      description: form.description?.trim() || null
    }
    if (props.rack) {
      await api.put(`/racks/${props.rack.id}`, payload)
      toast.success('Rak berhasil diperbarui')
    } else {
      await api.post('/racks', payload)
      toast.success('Rak berhasil ditambahkan')
    }
    emit('saved')
    emit('close')
  } catch (e) {
    const msg = e.response?.data?.message || e.message || 'Gagal menyimpan'
    const errors = e.response?.data?.errors
    if (errors) {
      const list = Object.values(errors).flat()
      toast.error(list.join(', '))
    } else {
      toast.error(msg)
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadWarehouses()
})
</script>
