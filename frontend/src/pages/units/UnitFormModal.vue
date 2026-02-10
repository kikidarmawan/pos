<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">{{ unit ? 'Edit Satuan' : 'Tambah Satuan Baru' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="label">Kode *</label>
          <input v-model="form.code" type="text" required class="input" placeholder="Contoh: MTR, RL" />
        </div>
        <div>
          <label class="label">Nama *</label>
          <input v-model="form.name" type="text" required class="input" placeholder="Contoh: Meter, Roll" />
        </div>
        <div>
          <label class="label">Deskripsi</label>
          <textarea v-model="form.description" rows="2" class="input" placeholder="Deskripsi satuan (opsional)"></textarea>
        </div>
        <div class="flex items-center gap-2">
          <input v-model="form.is_active" type="checkbox" id="unit_is_active" class="rounded border-gray-300 text-primary-600" />
          <label for="unit_is_active" class="text-sm text-gray-700">Aktif</label>
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
  unit: { type: Object, default: null }
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const submitting = ref(false)

const form = reactive({
  code: '',
  name: '',
  description: '',
  is_active: true
})

watch(() => props.unit, (u) => {
  if (u) {
    form.code = u.code || ''
    form.name = u.name || ''
    form.description = u.description || ''
    form.is_active = u.is_active ?? true
  } else {
    form.code = ''
    form.name = ''
    form.description = ''
    form.is_active = true
  }
}, { immediate: true })

const handleSubmit = async () => {
  submitting.value = true
  try {
    const payload = {
      code: form.code.trim(),
      name: form.name.trim(),
      description: form.description?.trim() || null,
      is_active: form.is_active
    }
    if (props.unit) {
      await api.put(`/units/${props.unit.id}`, payload)
      toast.success('Satuan berhasil diperbarui')
    } else {
      await api.post('/units', payload)
      toast.success('Satuan berhasil ditambahkan')
    }
    emit('saved')
    emit('close')
  } catch (e) {
    const msg = e.response?.data?.message || e.message || 'Gagal menyimpan'
    toast.error(msg)
  } finally {
    submitting.value = false
  }
}
</script>
