<template>
  <div>
    <!-- Hero -->
    <section
      class="relative overflow-hidden gradient-mesh px-4 pt-24 pb-32 sm:px-6 sm:pt-32 sm:pb-40 lg:px-8"
      aria-labelledby="hero-heading"
    >
      <div class="relative mx-auto max-w-4xl text-center">
        <h1
          id="hero-heading"
          class="text-4xl font-extrabold tracking-tight text-white opacity-0 sm:text-5xl lg:text-6xl animate-fade-in-up"
        >
          Kelola Toko & Kasir
          <span class="block mt-2 bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">
            Lebih Mudah
          </span>
        </h1>
        <p
          class="mx-auto mt-6 max-w-2xl text-lg text-slate-300 opacity-0 sm:text-xl animate-fade-in-up animation-delay-100"
        >
          Aplikasi Point of Sale untuk penjualan, pembelian, stok, dan laporan dalam satu sistem.
          Cocok untuk toko, retail, dan UMKM.
        </p>
        <div
          class="mt-10 flex flex-wrap items-center justify-center gap-4 opacity-0 animate-fade-in-up animation-delay-200"
        >
          <a :href="loginUrl" target="_blank" rel="noopener" class="btn-primary text-base shadow-lg shadow-indigo-500/25">
            Mulai Sekarang
          </a>
          <a href="#fitur" class="btn-outline border-slate-500 bg-slate-800/50 text-white hover:border-indigo-400 hover:text-indigo-300">
            Lihat Fitur
          </a>
        </div>
      </div>
      <!-- Decorative grid -->
      <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.02)_1px,transparent_1px)] bg-[size:64px_64px] pointer-events-none" aria-hidden="true" />
    </section>

    <!-- Features -->
    <section id="fitur" class="py-20 sm:py-28 bg-white" aria-labelledby="fitur-heading">
      <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h2 id="fitur-heading" class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
            Fitur Lengkap
          </h2>
          <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
            Semua yang Anda butuhkan untuk mengelola transaksi, stok, dan laporan bisnis.
          </p>
        </div>
        <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="(feature, i) in features"
            :key="feature.title"
            class="group relative rounded-2xl border border-slate-200 bg-slate-50/50 p-8 transition-all hover:border-indigo-200 hover:bg-white hover:shadow-xl hover:shadow-indigo-100/50"
          >
            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl transition-colors"
              :class="feature.iconBg"
            >
              <component :is="feature.icon" class="h-6 w-6" :class="feature.iconColor" />
            </div>
            <h3 class="mt-5 text-lg font-semibold text-slate-900">
              {{ feature.title }}
            </h3>
            <p class="mt-2 text-slate-600">
              {{ feature.description }}
            </p>
          </article>
        </div>
      </div>
    </section>

    <!-- Stats / Trust -->
    <section class="py-16 bg-slate-900 text-white" aria-label="Statistik">
      <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-8 lg:grid-cols-4">
          <div class="text-center">
            <p class="text-3xl font-bold text-indigo-400 sm:text-4xl">POS</p>
            <p class="mt-1 text-sm text-slate-400">Kasir Cepat</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-bold text-indigo-400 sm:text-4xl">Stok</p>
            <p class="mt-1 text-sm text-slate-400">Multi Gudang</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-bold text-indigo-400 sm:text-4xl">Laporan</p>
            <p class="mt-1 text-sm text-slate-400">Real-time</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-bold text-indigo-400 sm:text-4xl">Retur</p>
            <p class="mt-1 text-sm text-slate-400">Penjualan & Pembelian</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Untuk Siapa -->
    <section id="untuk-siapa" class="py-20 sm:py-28 bg-slate-50" aria-labelledby="untuk-siapa-heading">
      <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h2 id="untuk-siapa-heading" class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
            Untuk Siapa?
          </h2>
          <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
            Dirancang untuk berbagai skala bisnis.
          </p>
        </div>
        <div class="mt-16 grid gap-6 sm:grid-cols-3">
          <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm transition hover:shadow-md">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
              <StoreIcon class="h-6 w-6" />
            </div>
            <h3 class="mt-5 text-lg font-semibold text-slate-900">Toko & Retail</h3>
            <p class="mt-2 text-slate-600">Kelola penjualan harian, stok, dan pelanggan dengan satu sistem.</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm transition hover:shadow-md">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
              <BuildingIcon class="h-6 w-6" />
            </div>
            <h3 class="mt-5 text-lg font-semibold text-slate-900">UMKM</h3>
            <p class="mt-2 text-slate-600">Pembelian, penjualan, retur, dan laporan laba rugi terintegrasi.</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm transition hover:shadow-md">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
              <UserGroupIcon class="h-6 w-6" />
            </div>
            <h3 class="mt-5 text-lg font-semibold text-slate-900">Multi Kasir</h3>
            <p class="mt-2 text-slate-600">Peran dan hak akses untuk kasir, admin, dan manajer.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Platform Support -->
    <section id="platform" class="py-20 sm:py-28 bg-white border-t border-slate-200" aria-labelledby="platform-heading">
      <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h2 id="platform-heading" class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
            Dukung Berbagai Platform
          </h2>
          <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600">
            Akses POS System dari browser, desktop, atau perangkat mobile. Satu akun, dipakai di mana saja.
          </p>
        </div>
        <div class="mt-16 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-5">
          <div
            v-for="platform in platforms"
            :key="platform.name"
            class="group flex flex-col items-center rounded-2xl border border-slate-200 bg-slate-50/50 px-6 py-8 transition-all hover:border-indigo-200 hover:bg-indigo-50/30 hover:shadow-lg"
          >
            <div
              class="flex h-14 w-14 items-center justify-center rounded-2xl transition-colors"
              :class="platform.iconBg"
            >
              <component :is="platform.icon" class="h-7 w-7" :class="platform.iconColor" />
            </div>
            <h3 class="mt-4 text-base font-semibold text-slate-900">
              {{ platform.name }}
            </h3>
            <p class="mt-1 text-center text-sm text-slate-500">
              {{ platform.description }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-20 sm:py-28 bg-indigo-600" aria-labelledby="cta-heading">
      <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <h2 id="cta-heading" class="text-3xl font-bold text-white sm:text-4xl">
          Siap Mengelola Toko Anda?
        </h2>
        <p class="mt-4 text-lg text-indigo-100">
          Masuk ke aplikasi dan mulai gunakan POS System untuk transaksi harian.
        </p>
        <div class="mt-10">
          <a
            :href="loginUrl"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center justify-center rounded-xl bg-white px-8 py-3.5 font-semibold text-indigo-600 shadow-lg hover:bg-indigo-50 transition-colors"
          >
            Masuk ke Aplikasi
          </a>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import {
  CurrencyDollarIcon,
  ShoppingCartIcon,
  CubeIcon,
  ChartBarIcon,
  UserGroupIcon,
  PrinterIcon,
  BuildingStorefrontIcon as StoreIcon,
  BuildingOffice2Icon as BuildingIcon,
} from '@heroicons/vue/24/outline'
import LogoWeb from '~/components/platform-logos/LogoWeb.vue'
import LogoWindows from '~/components/platform-logos/LogoWindows.vue'
import LogoApple from '~/components/platform-logos/LogoApple.vue'
import LogoLinux from '~/components/platform-logos/LogoLinux.vue'
import LogoMobile from '~/components/platform-logos/LogoMobile.vue'

useSeoMeta({
  title: 'POS System - Aplikasi Kasir & Point of Sale untuk Toko',
  description: 'Aplikasi Point of Sale untuk penjualan, pembelian, stok, dan laporan. Kelola toko dan kasir lebih mudah - cocok untuk toko, retail, dan UMKM.',
  ogTitle: 'POS System - Aplikasi Kasir & Point of Sale',
  ogDescription: 'Kelola toko dan kasir lebih mudah. Penjualan, pembelian, stok, dan laporan dalam satu sistem.',
  ogType: 'website',
  ogLocale: 'id_ID',
})

const config = useRuntimeConfig()
const loginUrl = computed(() => {
  const base = (config.public?.appUrl || '').replace(/\/$/, '')
  return base ? `${base}/login` : '/login'
})

const features = [
  {
    title: 'Point of Sale',
    description: 'Kasir cepat dengan keranjang, pelanggan, dan berbagai metode pembayaran.',
    icon: CurrencyDollarIcon,
    iconBg: 'bg-indigo-100 group-hover:bg-indigo-200',
    iconColor: 'text-indigo-600',
  },
  {
    title: 'Penjualan & Pembelian',
    description: 'Daftar penjualan, pembelian, retur penjualan, dan retur pembelian terintegrasi.',
    icon: ShoppingCartIcon,
    iconBg: 'bg-emerald-100 group-hover:bg-emerald-200',
    iconColor: 'text-emerald-600',
  },
  {
    title: 'Produk & Stok',
    description: 'Kelola produk, kategori, satuan, gudang, dan penyesuaian stok.',
    icon: CubeIcon,
    iconBg: 'bg-amber-100 group-hover:bg-amber-200',
    iconColor: 'text-amber-600',
  },
  {
    title: 'Laporan',
    description: 'Laporan penjualan, pembelian, laba/rugi, stok, dan laporan lainnya.',
    icon: ChartBarIcon,
    iconBg: 'bg-blue-100 group-hover:bg-blue-200',
    iconColor: 'text-blue-600',
  },
  {
    title: 'Multi User & Hak Akses',
    description: 'Peran dan permission untuk kasir, admin, dan manajer.',
    icon: UserGroupIcon,
    iconBg: 'bg-purple-100 group-hover:bg-purple-200',
    iconColor: 'text-purple-600',
  },
  {
    title: 'Struk & Cetak',
    description: 'Cetak struk penjualan dan integrasi printer thermal.',
    icon: PrinterIcon,
    iconBg: 'bg-slate-200 group-hover:bg-slate-300',
    iconColor: 'text-slate-700',
  },
]

const platforms = [
  {
    name: 'Web',
    description: 'Browser di mana saja',
    icon: LogoWeb,
    iconBg: 'bg-sky-100 group-hover:bg-sky-200',
    iconColor: 'text-sky-600',
  },
  {
    name: 'Windows',
    description: 'Aplikasi desktop',
    icon: LogoWindows,
    iconBg: 'bg-blue-100 group-hover:bg-blue-200',
    iconColor: 'text-blue-600',
  },
  {
    name: 'macOS',
    description: 'Aplikasi desktop',
    icon: LogoApple,
    iconBg: 'bg-slate-200 group-hover:bg-slate-300',
    iconColor: 'text-slate-700',
  },
  {
    name: 'Linux',
    description: 'Aplikasi desktop',
    icon: LogoLinux,
    iconBg: 'bg-amber-100 group-hover:bg-amber-200',
    iconColor: 'text-amber-700',
  },
  {
    name: 'Mobile',
    description: 'PWA di smartphone',
    icon: LogoMobile,
    iconBg: 'bg-emerald-100 group-hover:bg-emerald-200',
    iconColor: 'text-emerald-600',
  },
]
</script>
