<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-700 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
          <h2 class="text-3xl font-bold text-gray-900">POS System</h2>
          <p class="mt-2 text-sm text-gray-600">Silakan login untuk melanjutkan</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label for="email" class="label">Email</label>
            <input id="email" v-model="form.email" type="email" required class="input" placeholder="admin@pos.com" />
          </div>

          <div>
            <label for="password" class="label">Password</label>
            <input id="password" v-model="form.password" type="password" required class="input"
              placeholder="••••••••" />
          </div>

          <button type="submit" :disabled="loading" class="w-full btn btn-primary">
            <span v-if="!loading">Login</span>
            <span v-else>Loading...</span>
          </button>
        </form>

        <div class="mt-6 text-center">
          <p class="text-xs text-gray-500">
            Demo Credentials:<br />
            Email: admin@pos.com | Password: password
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: ''
})

const loading = ref(false)

const handleLogin = async () => {
  loading.value = true
  const success = await authStore.login(form.value)
  loading.value = false

  if (success) {
    router.push({ name: 'Dashboard' })
  }
}
</script>
