<template>
  <footer class="border-t border-slate-200 bg-slate-900 text-slate-400">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="flex flex-col items-center justify-between gap-6 sm:flex-row">
        <div class="flex items-center gap-2">
          <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white">POS</span>
          <span class="font-semibold text-slate-300">POS System</span>
        </div>
        <div class="flex items-center gap-8 text-sm">
          <a href="#fitur" class="hover:text-white transition-colors">Fitur</a>
          <a href="#untuk-siapa" class="hover:text-white transition-colors">Untuk Siapa</a>
          <a href="#platform" class="hover:text-white transition-colors">Platform</a>
          <a :href="loginUrl" target="_blank" rel="noopener" class="hover:text-white transition-colors">Masuk</a>
        </div>
      </div>
      <p class="mt-8 border-t border-slate-800 pt-8 text-center text-sm">
        &copy; {{ new Date().getFullYear() }} POS System. Point of Sale untuk toko dan UMKM.
      </p>
    </div>
  </footer>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const loginUrl = computed(() => {
  const base = (config.public?.appUrl || '').replace(/\/$/, '')
  return base ? `${base}/login` : '/login'
})
</script>
