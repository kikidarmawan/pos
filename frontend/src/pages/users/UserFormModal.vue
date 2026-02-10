<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">{{ user ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="label">Nama *</label>
          <input v-model="form.name" type="text" required class="input" placeholder="Nama lengkap" />
        </div>
        <div>
          <label class="label">Email *</label>
          <input v-model="form.email" type="email" required class="input" placeholder="email@example.com" />
        </div>
        <div>
          <label class="label">{{ user ? 'Password (kosongkan jika tidak ubah)' : 'Password *' }}</label>
          <input v-model="form.password" type="password" :required="!user" class="input" placeholder="Min. 8 karakter" minlength="8" autocomplete="new-password" />
        </div>
        <div>
          <label class="label">Role</label>
          <div class="space-y-2 max-h-32 overflow-y-auto border rounded p-3 bg-gray-50">
            <label v-for="r in roles" :key="r.id" class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.roles" type="checkbox" :value="r.name" class="rounded border-gray-300 text-primary-600" />
              <span class="text-sm">{{ r.name }}</span>
            </label>
            <p v-if="!roles.length" class="text-sm text-gray-500">Belum ada role</p>
          </div>
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
import { ref, reactive, watch, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import api from '@/utils/axios'

const props = defineProps({
  user: { type: Object, default: null }
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const submitting = ref(false)
const roles = ref([])

const form = reactive({
  name: '',
  email: '',
  password: '',
  roles: []
})

onMounted(loadRoles)
async function loadRoles() {
  try {
    const res = await api.get('/roles', { params: { per_page: 100 } })
    roles.value = res.data.data ?? res.data
  } catch (e) {
    toast.error('Gagal memuat role')
  }
}

watch(() => props.user, (u) => {
  if (u) {
    form.name = u.name || ''
    form.email = u.email || ''
    form.password = ''
    form.roles = (u.roles || []).map(r => r.name)
  } else {
    form.name = ''
    form.email = ''
    form.password = ''
    form.roles = []
  }
}, { immediate: true })

const handleSubmit = async () => {
  submitting.value = true
  try {
    const payload = {
      name: form.name.trim(),
      email: form.email.trim(),
      roles: form.roles
    }
    if (form.password) payload.password = form.password
    if (props.user) {
      await api.put(`/users/${props.user.id}`, payload)
      toast.success('Pengguna berhasil diperbarui')
    } else {
      if (!form.password) {
        toast.error('Password wajib diisi')
        submitting.value = false
        return
      }
      payload.password = form.password
      await api.post('/users', payload)
      toast.success('Pengguna berhasil ditambahkan')
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
