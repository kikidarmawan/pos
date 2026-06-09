<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 my-8">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">{{ driver ? 'Edit Sopir' : 'Tambah Sopir Baru' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="label">No. KTP *</label>
            <input v-model="form.no_ktp" type="text" required maxlength="16" @input="form.no_ktp = form.no_ktp.replace(/[^0-9]/g, '')" class="input" placeholder="16 digit angka NIK" />
          </div>
          <div>
            <label class="label">Nama *</label>
            <input v-model="form.name" type="text" required maxlength="60" class="input" placeholder="Nama lengkap sopir" />
          </div>
          <div class="md:col-span-2">
            <label class="label">Alamat</label>
            <textarea v-model="form.address" rows="2" class="input" placeholder="Alamat lengkap"></textarea>
          </div>
          <div>
            <label class="label">Jenis Kelamin</label>
            <select v-model="form.gender" class="input">
              <option value="">Pilih</option>
              <option value="L">Laki-laki</option>
              <option value="P">Perempuan</option>
            </select>
          </div>
          <div>
            <label class="label">No. HP *</label>
            <input v-model="form.phone" type="text" required maxlength="20" @input="form.phone = form.phone.replace(/[^0-9]/g, '')" class="input" placeholder="08xxxxxxxxxx" />
          </div>
          <div>
            <label class="label">Tempat Lahir</label>
            <input v-model="form.birth_place" type="text" maxlength="50" class="input" placeholder="Kota" />
          </div>
          <div>
            <label class="label">Tgl Lahir</label>
            <input v-model="form.birth_date" type="date" class="input" />
          </div>
          
          <div>
            <label class="label">Foto KTP (Opsional)</label>
            <input type="file" @change="onKtpPhotoChange" accept="image/*" class="input p-1" />
            
            <div v-if="ktpPreviewUrl" class="mt-2">
              <img :src="ktpPreviewUrl" class="h-32 object-contain border rounded" alt="Preview KTP" />
            </div>
            <div v-else-if="driver && driver.ktp_photo" class="mt-2 text-sm text-blue-600">
              <a :href="getImageUrl(driver.ktp_photo)" target="_blank" class="hover:underline flex items-center gap-2">
                <img :src="getImageUrl(driver.ktp_photo)" class="h-16 object-cover border rounded" />
                Lihat foto KTP saat ini
              </a>
            </div>
          </div>
          
          <div>
            <label class="label">Foto Profil (Opsional)</label>
            <input type="file" @change="onPhotoChange" accept="image/*" class="input p-1" />
            
            <div v-if="photoPreviewUrl" class="mt-2">
              <img :src="photoPreviewUrl" class="h-32 object-contain border rounded" alt="Preview Profil" />
            </div>
            <div v-else-if="driver && driver.photo" class="mt-2 text-sm text-blue-600">
              <a :href="getImageUrl(driver.photo)" target="_blank" class="hover:underline flex items-center gap-2">
                <img :src="getImageUrl(driver.photo)" class="h-16 object-cover border rounded" />
                Lihat foto Profil saat ini
              </a>
            </div>
          </div>
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
import { ref, reactive, watch, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import api from '@/utils/axios'

const props = defineProps({
  driver: { type: Object, default: null }
})

const getImageUrl = (path) => {
  if (!path) return '';
  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';
  return `${baseUrl}/storage/${path}`;
}

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const submitting = ref(false)

const form = reactive({
  no_ktp: '',
  name: '',
  address: '',
  gender: '',
  birth_place: '',
  birth_date: '',
  phone: ''
})

const ktpPhoto = ref(null)
const ktpPreviewUrl = ref(null)

const photo = ref(null)
const photoPreviewUrl = ref(null)

const clearPreviews = () => {
  if (ktpPreviewUrl.value) URL.revokeObjectURL(ktpPreviewUrl.value)
  if (photoPreviewUrl.value) URL.revokeObjectURL(photoPreviewUrl.value)
  ktpPreviewUrl.value = null
  photoPreviewUrl.value = null
}

onUnmounted(() => {
  clearPreviews()
})

watch(() => props.driver, (d) => {
  if (d) {
    form.no_ktp = d.no_ktp || ''
    form.name = d.name || ''
    form.address = d.address || ''
    form.gender = d.gender || ''
    form.birth_place = d.birth_place || ''
    form.birth_date = d.birth_date ? d.birth_date.split('T')[0] : ''
    form.phone = d.phone || ''
  } else {
    form.no_ktp = ''
    form.name = ''
    form.address = ''
    form.gender = ''
    form.birth_place = ''
    form.birth_date = ''
    form.phone = ''
  }
  ktpPhoto.value = null
  photo.value = null
  clearPreviews()
}, { immediate: true })

const onKtpPhotoChange = (e) => {
  if (ktpPreviewUrl.value) URL.revokeObjectURL(ktpPreviewUrl.value)
  if (e.target.files.length > 0) {
    ktpPhoto.value = e.target.files[0]
    ktpPreviewUrl.value = URL.createObjectURL(ktpPhoto.value)
  } else {
    ktpPhoto.value = null
    ktpPreviewUrl.value = null
  }
}

const onPhotoChange = (e) => {
  if (photoPreviewUrl.value) URL.revokeObjectURL(photoPreviewUrl.value)
  if (e.target.files.length > 0) {
    photo.value = e.target.files[0]
    photoPreviewUrl.value = URL.createObjectURL(photo.value)
  } else {
    photo.value = null
    photoPreviewUrl.value = null
  }
}

const handleSubmit = async () => {
  if (form.no_ktp.length > 16) {
    toast.error('No KTP maksimal 16 karakter')
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('no_ktp', form.no_ktp.trim())
    formData.append('name', form.name.trim())
    formData.append('phone', form.phone.trim())
    
    if (form.address) formData.append('address', form.address.trim())
    if (form.gender) formData.append('gender', form.gender)
    if (form.birth_place) formData.append('birth_place', form.birth_place.trim())
    if (form.birth_date) formData.append('birth_date', form.birth_date)

    if (ktpPhoto.value) formData.append('ktp_photo', ktpPhoto.value)
    if (photo.value) formData.append('photo', photo.value)

    if (props.driver) {
      await api.post(`/drivers/${props.driver.id}/update-with-file`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      toast.success('Sopir berhasil diperbarui')
    } else {
      await api.post('/drivers', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      toast.success('Sopir berhasil ditambahkan')
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
