<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">{{ vehicle ? 'Edit Kendaraan' : 'Tambah Kendaraan Baru' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="label">Plat Nomor *</label>
          <input v-model="form.license_plate" type="text" required maxlength="20" class="input" placeholder="Misal: B 1234 CD" />
        </div>
        <div>
          <label class="label">Nama Kendaraan *</label>
          <input v-model="form.name" type="text" required class="input" placeholder="Misal: Truk Engkel Hino" />
        </div>
        <div>
          <label class="label">Jenis Kendaraan</label>
          <input v-model="form.type" type="text" class="input" placeholder="Misal: Truk, Pick Up" />
        </div>

        <div class="flex gap-3 pt-4 border-t mt-6">
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
  vehicle: { type: Object, default: null }
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const submitting = ref(false)

const form = reactive({
  license_plate: '',
  name: '',
  type: ''
})

watch(() => props.vehicle, (v) => {
  if (v) {
    form.license_plate = v.license_plate
    form.name = v.name
    form.type = v.type || ''
  } else {
    form.license_plate = ''
    form.name = ''
    form.type = ''
  }
}, { immediate: true })

const handleSubmit = async () => {
  submitting.value = true
  try {
    if (props.vehicle) {
      await api.put(`/vehicles/${props.vehicle.id}`, form)
      toast.success('Kendaraan berhasil diperbarui')
    } else {
      await api.post('/vehicles', form)
      toast.success('Kendaraan berhasil ditambahkan')
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
