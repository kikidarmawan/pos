<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">{{ supplier ? 'Edit Supplier' : 'Tambah Supplier Baru' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="label">Kode *</label>
          <input v-model="form.code" type="text" required class="input" placeholder="Contoh: SUP-001" />
        </div>
        <div>
          <label class="label">Nama *</label>
          <input v-model="form.name" type="text" required class="input" placeholder="Nama perusahaan / supplier" />
        </div>
        <div>
          <label class="label">Kontak Person</label>
          <input v-model="form.contact_person" type="text" class="input" placeholder="Nama penanggung jawab" />
        </div>
        <div>
          <label class="label">No. Telepon</label>
          <input v-model="form.phone" type="text" class="input" placeholder="08xxxxxxxxxx" />
        </div>
        <div>
          <label class="label">Email</label>
          <input v-model="form.email" type="email" class="input" placeholder="email@supplier.com" />
        </div>
        <div>
          <label class="label">Alamat</label>
          <textarea v-model="form.address" rows="2" class="input" placeholder="Alamat lengkap"></textarea>
        </div>
        <div class="flex items-center gap-2">
          <input v-model="form.is_active" type="checkbox" id="sup_is_active" class="rounded border-gray-300 text-primary-600" />
          <label for="sup_is_active" class="text-sm text-gray-700">Aktif</label>
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
  supplier: { type: Object, default: null }
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const submitting = ref(false)

const form = reactive({
  code: '',
  name: '',
  contact_person: '',
  phone: '',
  email: '',
  address: '',
  is_active: true
})

watch(() => props.supplier, (s) => {
  if (s) {
    form.code = s.code || ''
    form.name = s.name || ''
    form.contact_person = s.contact_person || ''
    form.phone = s.phone || ''
    form.email = s.email || ''
    form.address = s.address || ''
    form.is_active = s.is_active ?? true
  } else {
    form.code = ''
    form.name = ''
    form.contact_person = ''
    form.phone = ''
    form.email = ''
    form.address = ''
    form.is_active = true
  }
}, { immediate: true })

const handleSubmit = async () => {
  submitting.value = true
  try {
    const payload = {
      code: form.code.trim(),
      name: form.name.trim(),
      contact_person: form.contact_person?.trim() || null,
      phone: form.phone?.trim() || null,
      email: form.email?.trim() || null,
      address: form.address?.trim() || null,
      is_active: form.is_active
    }
    if (props.supplier) {
      await api.put(`/suppliers/${props.supplier.id}`, payload)
      toast.success('Supplier berhasil diperbarui')
    } else {
      await api.post('/suppliers', payload)
      toast.success('Supplier berhasil ditambahkan')
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
