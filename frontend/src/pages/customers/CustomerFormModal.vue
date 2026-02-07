<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">{{ customer ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div v-if="customer">
          <label class="label">Kode</label>
          <input v-model="form.code" type="text" class="input" placeholder="CUS-0001" />
        </div>
        <div>
          <label class="label">Nama *</label>
          <input v-model="form.name" type="text" required class="input" placeholder="Nama lengkap pelanggan" />
        </div>
        <div>
          <label class="label">No. Telepon</label>
          <input v-model="form.phone" type="text" class="input" placeholder="08xxxxxxxxxx" />
        </div>
        <div>
          <label class="label">Alamat</label>
          <textarea v-model="form.address" rows="3" class="input" placeholder="Alamat lengkap"></textarea>
        </div>
        <div v-if="customer" class="flex items-center gap-2">
          <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-gray-300 text-primary-600" />
          <label for="is_active" class="text-sm text-gray-700">Aktif</label>
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
  customer: { type: Object, default: null }
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const submitting = ref(false)

const form = reactive({
  code: '',
  name: '',
  phone: '',
  address: '',
  is_active: true
})

watch(() => props.customer, (c) => {
  if (c) {
    form.code = c.code || ''
    form.name = c.name || ''
    form.phone = c.phone || ''
    form.address = c.address || ''
    form.is_active = c.is_active ?? true
  } else {
    form.code = ''
    form.name = ''
    form.phone = ''
    form.address = ''
    form.is_active = true
  }
}, { immediate: true })

const handleSubmit = async () => {
  submitting.value = true
  try {
    const payload = {
      name: form.name,
      phone: form.phone || null,
      address: form.address || null,
      is_active: form.is_active
    }
    if (props.customer) {
      if (form.code) payload.code = form.code
      const res = await api.put(`/customers/${props.customer.id}`, payload)
      toast.success('Pelanggan berhasil diperbarui')
      emit('saved', res.data.customer)
    } else {
      if (form.code) payload.code = form.code
      const res = await api.post('/customers', payload)
      toast.success('Pelanggan berhasil ditambahkan')
      emit('saved', res.data.customer)
    }
    emit('close')
  } catch (e) {
    const msg = e.response?.data?.message || e.message || 'Gagal menyimpan'
    toast.error(msg)
  } finally {
    submitting.value = false
  }
}
</script>
