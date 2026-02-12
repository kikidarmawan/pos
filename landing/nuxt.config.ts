// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },
  modules: ['@nuxtjs/tailwindcss'],
  app: {
    head: {
      htmlAttrs: { lang: 'id' },
      title: 'POS System - Aplikasi Kasir & Point of Sale untuk Toko',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        {
          name: 'description',
          content: 'Aplikasi Point of Sale untuk penjualan, pembelian, stok, dan laporan. Kelola toko dan kasir lebih mudah - cocok untuk toko, retail, dan UMKM.',
        },
        {
          name: 'keywords',
          content: 'POS, point of sale, aplikasi kasir, kasir toko, penjualan, pembelian, stok, laporan, UMKM, retail',
        },
        { name: 'theme-color', content: '#0f172a' },
        { property: 'og:type', content: 'website' },
        { property: 'og:locale', content: 'id_ID' },
      ],
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
      ],
    },
  },
  tailwindcss: {
    cssPath: '~/assets/css/main.css',
  },
  compatibilityDate: '2024-11-01',
  runtimeConfig: {
    public: {
      appUrl: process.env.NUXT_PUBLIC_APP_URL || '',
    },
  },
})
