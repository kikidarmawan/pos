<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">{{ category ? 'Edit Kategori' : 'Tambah Kategori Baru' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="label">Kode *</label>
          <input v-model="form.code" type="text" required class="input" placeholder="Contoh: FNB, NON-FNB" />
        </div>
        <div>
          <label class="label">Nama *</label>
          <input v-model="form.name" type="text" required class="input" placeholder="Contoh: Makanan & Minuman" />
        </div>
        <div>
          <label class="label">Deskripsi</label>
          <textarea v-model="form.description" rows="2" class="input" placeholder="Deskripsi kategori (opsional)"></textarea>
        </div>
        <div class="flex items-center gap-2">
          <input v-model="form.is_active" type="checkbox" id="cat_is_active" class="rounded border-gray-300 text-primary-600" />
          <label for="cat_is_active" class="text-sm text-gray-700">Aktif</label>
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
  category: { type: Object, default: null }
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

watch(() => props.category, (c) => {
  if (c) {
    form.code = c.code || ''
    form.name = c.name || ''
    form.description = c.description || ''
    form.is_active = c.is_active ?? true
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
    if (props.category) {
      await api.put(`/categories/${props.category.id}`, payload)
      toast.success('Kategori berhasil diperbarui')
    } else {
      await api.post('/categories', payload)
      toast.success('Kategori berhasil ditambahkan')
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
