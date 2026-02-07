<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Identitas Toko</h1>
    <p class="text-gray-600 mb-6">Data ini akan muncul di struk cetak.</p>

    <div class="max-w-2xl">
      <div class="card">
        <form @submit.prevent="handleSave" class="space-y-4">
          <div>
            <label class="label">Nama Toko</label>
            <input v-model="form.name" type="text" required class="input" placeholder="Contoh: Toko Maju Jaya" />
          </div>
          <div>
            <label class="label">Alamat</label>
            <textarea v-model="form.address" rows="2" class="input" placeholder="Alamat lengkap toko"></textarea>
          </div>
          <div>
            <label class="label">No. Telepon</label>
            <input v-model="form.phone" type="text" class="input" placeholder="08xxxxxxxxxx" />
          </div>
          <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" placeholder="email@toko.com" />
          </div>
          <button type="submit" :disabled="loading" class="btn btn-primary">
            {{ loading ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'

const toast = useToast()
const loading = ref(false)
const form = ref({
  name: '',
  address: '',
  phone: '',
  email: ''
})

const fetchStore = async () => {
  try {
    const res = await api.get('/store')
    form.value = {
      name: res.data.name ?? '',
      address: res.data.address ?? '',
      phone: res.data.phone ?? '',
      email: res.data.email ?? ''
    }
  } catch {
    toast.error('Gagal memuat data toko')
  }
}

const handleSave = async () => {
  loading.value = true
  try {
    await api.put('/store', form.value)
    toast.success('Identitas toko berhasil disimpan')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan')
  } finally {
    loading.value = false
  }
}

onMounted(fetchStore)
</script>
