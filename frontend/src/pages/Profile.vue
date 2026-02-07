<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Profil Saya</h1>

    <div class="max-w-2xl">
      <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4">Informasi Pengguna</h2>
        <div class="space-y-3">
          <div>
            <p class="text-sm text-gray-600">Nama</p>
            <p class="font-medium">{{ user?.name }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Email</p>
            <p class="font-medium">{{ user?.email }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Role</p>
            <div class="flex gap-2 mt-1">
              <span v-for="role in user?.roles" :key="role.id" class="badge badge-info">
                {{ role.name }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <h2 class="text-xl font-bold mb-4">Ubah Password</h2>
        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label class="label">Password Lama</label>
            <input v-model="passwordForm.current_password" type="password" required class="input" />
          </div>
          <div>
            <label class="label">Password Baru</label>
            <input v-model="passwordForm.new_password" type="password" required class="input" />
          </div>
          <div>
            <label class="label">Konfirmasi Password Baru</label>
            <input v-model="passwordForm.new_password_confirmation" type="password" required class="input" />
          </div>
          <button type="submit" :disabled="loading" class="btn btn-primary">
            {{ loading ? 'Menyimpan...' : 'Ubah Password' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'

const authStore = useAuthStore()
const toast = useToast()

const user = computed(() => authStore.user)
const loading = ref(false)

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const changePassword = async () => {
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    toast.error('Konfirmasi password tidak cocok')
    return
  }

  loading.value = true

  try {
    await api.put('/change-password', passwordForm.value)
    toast.success('Password berhasil diubah')
    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal mengubah password'
    toast.error(message)
  } finally {
    loading.value = false
  }
}
</script>
