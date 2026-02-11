<template>
  <div class="login-page min-h-screen flex">
    <!-- Left: POS background image (hidden on small screens) -->
    <div class="login-left hidden lg:block lg:w-[62%] xl:w-[66%] 2xl:w-[68%] relative overflow-hidden">
      <div class="login-left-bg" role="img" aria-label="Point of sale - kasir menggunakan mesin POS di toko"></div>
      <div class="login-left-overlay"></div>
      <div class="absolute inset-0 flex flex-col justify-end px-12 xl:px-20 pb-12 z-10">
        <p class="text-white/95 text-lg leading-relaxed max-w-md">
          Kelola penjualan, stok, dan laporan toko dalam satu aplikasi.
        </p>
      </div>
    </div>

    <!-- Right: Form (seperti referensi - putih, logo + Masuk di atas) -->
    <div class="login-form flex-1 flex items-center justify-center px-4 sm:px-5 lg:px-6 py-12 bg-white min-w-0">
      <div class="w-full max-w-[400px]">
        <!-- Logo di atas (selalu tampil) -->
        <div class="text-center mb-8">
          <div
            class="inline-flex w-14 h-14 rounded-2xl bg-primary-500 text-white items-center justify-center shadow-lg mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-slate-800">POS System</h2>
        </div>

        <div class="bg-white p-0">
          <h3 class="text-center text-xl font-semibold text-slate-800 mb-6">Masuk</h3>

          <form @submit.prevent="handleLogin" class="space-y-5">
            <div>
              <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                  </svg>
                </span>
                <input id="email" v-model="form.email" type="email" required autocomplete="email"
                  class="login-input w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white transition duration-200"
                  placeholder="admin@pos.com" />
              </div>
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                </span>
                <input id="password" v-model="form.password" type="password" required autocomplete="current-password"
                  class="login-input w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white transition duration-200"
                  placeholder="••••••••" />
              </div>
            </div>

            <button type="submit" :disabled="loading"
              class="w-full mt-2 py-3.5 px-4 rounded-lg font-semibold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed transition duration-200 flex items-center justify-center gap-2 tracking-wide uppercase text-sm">
              <span v-if="!loading">Masuk</span>
              <span v-else class="flex items-center gap-2">
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                  viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
                Memproses...
              </span>
            </button>
          </form>

          <div class="mt-6 pt-6 border-t border-slate-100">
            <p class="text-xs text-slate-500 text-center">
              Akun demo: <span class="font-mono text-slate-600">admin@pos.com</span> / <span
                class="font-mono text-slate-600">password</span>
            </p>
          </div>
        </div>

        <p class="mt-8 text-center text-xs text-slate-400">
          © {{ new Date().getFullYear() }} POS System
        </p>
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

<style scoped>
.login-page {
  font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* Left: POS background image (Unsplash - person using POS in store) */
.login-left {
  position: relative;
  min-height: 100vh;
}

.login-left-bg {
  position: absolute;
  inset: 0;
  background-image: url('https://images.unsplash.com/photo-1647427017067-8f33ccbae493?w=1200&q=80');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.login-left-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.75) 0%, rgba(59, 130, 246, 0.6) 50%, rgba(6, 182, 212, 0.5) 100%);
  pointer-events: none;
}

.login-form {
  background-color: #fff;
}

.login-input:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>
