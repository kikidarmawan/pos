<template>
  <div class="max-w-md mx-auto py-12 text-center">
    <div v-if="status === 'success'" class="card p-8">
      <div class="w-16 h-16 mx-auto rounded-full bg-green-100 flex items-center justify-center mb-4">
        <CheckCircleIcon class="w-10 h-10 text-green-600" />
      </div>
      <h1 class="text-xl font-bold text-gray-900">Pembayaran Berhasil</h1>
      <p class="mt-2 text-gray-600">Langganan Anda telah diperpanjang. Anda dapat menggunakan aplikasi seperti biasa.</p>
      <router-link to="/" class="btn btn-primary mt-6">Kembali ke Dashboard</router-link>
    </div>
    <div v-else-if="status === 'pending'" class="card p-8">
      <div class="w-16 h-16 mx-auto rounded-full bg-amber-100 flex items-center justify-center mb-4">
        <ClockIcon class="w-10 h-10 text-amber-600" />
      </div>
      <h1 class="text-xl font-bold text-gray-900">Menunggu Pembayaran</h1>
      <p class="mt-2 text-gray-600">Silakan selesaikan pembayaran Anda. Langganan akan aktif otomatis setelah pembayaran diterima.</p>
      <router-link to="/" class="btn btn-primary mt-6">Kembali ke Dashboard</router-link>
    </div>
    <div v-else class="card p-8">
      <div class="w-16 h-16 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-4">
        <XCircleIcon class="w-10 h-10 text-red-600" />
      </div>
      <h1 class="text-xl font-bold text-gray-900">Pembayaran Gagal atau Dibatalkan</h1>
      <p class="mt-2 text-gray-600">Anda dapat mencoba lagi dengan memilih paket dan melakukan pembayaran ulang.</p>
      <router-link to="/" class="btn btn-primary mt-6">Kembali ke Dashboard</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useSubscriptionStore } from '@/stores/subscription'
import { CheckCircleIcon, ClockIcon, XCircleIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const subscriptionStore = useSubscriptionStore()
const status = ref('success') // success | pending | error

onMounted(() => {
  const q = route.query?.status
  if (q === 'pending') status.value = 'pending'
  else if (q === 'error') status.value = 'error'
  else status.value = 'success'
  subscriptionStore.fetchCurrent()
  subscriptionStore.closePayModal()
})
</script>
