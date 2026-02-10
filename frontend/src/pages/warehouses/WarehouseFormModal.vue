<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">{{ warehouse ? 'Edit Gudang' : 'Tambah Gudang Baru' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="label">Kode *</label>
          <input v-model="form.code" type="text" required class="input" placeholder="Contoh: GD-01" />
        </div>
        <div>
          <label class="label">Nama *</label>
          <input v-model="form.name" type="text" required class="input" placeholder="Contoh: Gudang Utama" />
        </div>
        <div>
          <label class="label">Alamat</label>
          <textarea v-model="form.address" rows="2" class="input" placeholder="Alamat gudang (opsional)"></textarea>
        </div>
        <div>
          <label class="label">Telepon</label>
          <input v-model="form.phone" type="text" class="input" placeholder="Contoh: 021-xxx" maxlength="20" />
        </div>
        <div class="flex items-center gap-2">
          <input v-model="form.is_active" type="checkbox" id="wh_is_active" class="rounded border-gray-300 text-primary-600" />
          <label for="wh_is_active" class="text-sm text-gray-700">Aktif</label>
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
import { ref, reactive, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import api from '@/utils/axios'

const props = defineProps({
  warehouse: { type: Object, default: null }
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const submitting = ref(false)

const form = reactive({
  code: '',
  name: '',
  address: '',
  phone: '',
  is_active: true
})

watch(() => props.warehouse, (w) => {
  if (w) {
    form.code = w.code || ''
    form.name = w.name || ''
    form.address = w.address || ''
    form.phone = w.phone || ''
    form.is_active = w.is_active ?? true
  } else {
    form.code = ''
    form.name = ''
    form.address = ''
    form.phone = ''
    form.is_active = true
  }
}, { immediate: true })

const handleSubmit = async () => {
  submitting.value = true
  try {
    const payload = {
      code: form.code.trim(),
      name: form.name.trim(),
      address: form.address?.trim() || null,
      phone: form.phone?.trim() || null,
      is_active: form.is_active
    }
    if (props.warehouse) {
      await api.put(`/warehouses/${props.warehouse.id}`, payload)
      toast.success('Gudang berhasil diperbarui')
    } else {
      await api.post('/warehouses', payload)
      toast.success('Gudang berhasil ditambahkan')
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
</script>
