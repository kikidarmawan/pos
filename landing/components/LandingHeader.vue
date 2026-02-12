<template>
  <header
    class="sticky top-0 z-50 w-full border-b border-slate-200/80 bg-white/90 backdrop-blur-xl transition-shadow"
    :class="{ 'shadow-sm': scrolled }"
  >
    <nav class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
      <NuxtLink to="/" class="flex items-center gap-2.5 font-bold text-slate-900">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-sm font-bold text-white shadow-lg">
          POS
        </span>
        <span class="text-lg">POS System</span>
      </NuxtLink>
      <div class="flex items-center gap-6">
        <a href="#fitur" class="hidden text-sm font-medium text-slate-600 hover:text-indigo-600 sm:inline-block transition-colors">
          Fitur
        </a>
        <a href="#untuk-siapa" class="hidden text-sm font-medium text-slate-600 hover:text-indigo-600 sm:inline-block transition-colors">
          Untuk Siapa
        </a>
        <a href="#platform" class="hidden text-sm font-medium text-slate-600 hover:text-indigo-600 sm:inline-block transition-colors">
          Platform
        </a>
        <a
          :href="loginUrl"
          target="_blank"
          rel="noopener"
          class="btn-primary text-sm"
        >
          Masuk ke Aplikasi
        </a>
      </div>
    </nav>
  </header>
</template>

<script setup lang="ts">
const scrolled = ref(false)
const config = useRuntimeConfig()
const loginUrl = computed(() => {
  const base = (config.public?.appUrl || '').replace(/\/$/, '')
  return base ? `${base}/login` : '/login'
})

const onScroll = () => {
  scrolled.value = window.scrollY > 8
}
onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
})
onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
})
</script>
