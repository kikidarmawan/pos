<template>
  <div class="min-h-screen flex flex-col">
    <!-- Banner update PWA tersedia (service worker baru) -->
    <div
      v-if="pwaUpdateReady"
      class="bg-primary-600 text-white px-4 py-2 flex items-center justify-center gap-3 text-sm shrink-0"
    >
      <span>Versi baru tersedia.</span>
      <button
        type="button"
        @click="applyPwaUpdate"
        class="font-semibold underline hover:no-underline"
      >
        Muat ulang sekarang
      </button>
      <button
        type="button"
        @click="pwaUpdateReady = false"
        class="opacity-80 hover:opacity-100"
        aria-label="Tutup"
      >
        ×
      </button>
    </div>
    <!-- Banner update dari cek versi server -->
    <div
      v-if="serverUpdateAvailable"
      class="bg-amber-500 text-gray-900 px-4 py-2 flex items-center justify-center gap-3 text-sm shrink-0"
    >
      <span>Versi {{ serverVersion }} tersedia. Muat ulang untuk memperbarui.</span>
      <button
        type="button"
        @click="reloadPage"
        class="font-semibold underline hover:no-underline"
      >
        Muat ulang
      </button>
      <button
        type="button"
        @click="serverUpdateAvailable = false"
        class="opacity-80 hover:opacity-100"
        aria-label="Tutup"
      >
        ×
      </button>
    </div>
    <router-view class="flex-1" />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/utils/axios'

const authStore = useAuthStore()
const pwaUpdateReady = ref(false)
const serverUpdateAvailable = ref(false)
const serverVersion = ref('')
let pwaUpdateApply = null
let versionCheckInterval = null

const currentVersion = typeof __APP_VERSION__ !== 'undefined' ? __APP_VERSION__ : '1.0.0'

/** Bandingkan versi semver (a > b => 1, a < b => -1, sama => 0) */
function compareVersions(a, b) {
  const pa = (a || '0').split('.').map(Number)
  const pb = (b || '0').split('.').map(Number)
  for (let i = 0; i < Math.max(pa.length, pb.length); i++) {
    const va = pa[i] || 0
    const vb = pb[i] || 0
    if (va > vb) return 1
    if (va < vb) return -1
  }
  return 0
}

async function checkServerVersion() {
  try {
    const res = await api.get('/version')
    const v = res.data?.version
    if (v && compareVersions(v, currentVersion) > 0) {
      serverVersion.value = v
      serverUpdateAvailable.value = true
    }
  } catch (_) {
    // Abaikan jika endpoint gagal (dev / offline)
  }
}

function reloadPage() {
  window.location.reload()
}

function applyPwaUpdate() {
  if (typeof pwaUpdateApply === 'function') {
    pwaUpdateApply(true)
  }
  pwaUpdateReady.value = false
}

onMounted(() => {
  authStore.checkAuth()

  // PWA: prompt update saat ada service worker baru
  if (import.meta.env.PROD && typeof window !== 'undefined' && 'serviceWorker' in window) {
    import('virtual:pwa-register').then(({ registerSW }) => {
      pwaUpdateApply = registerSW({
        immediate: true,
        onNeedRefresh() {
          pwaUpdateReady.value = true
        },
      })
    }).catch(() => {})
  }

  // Cek versi server secara berkala (setiap 30 menit)
  checkServerVersion()
  versionCheckInterval = setInterval(checkServerVersion, 30 * 60 * 1000)
})

onUnmounted(() => {
  if (versionCheckInterval) clearInterval(versionCheckInterval)
})
</script>
