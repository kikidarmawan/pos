# POS System - Landing Page

Landing page untuk aplikasi POS System. Dibuat dengan **Nuxt 3** (SSR/SSG) untuk performa dan SEO yang baik.

## Fitur

- **SEO**: Meta tags, Open Graph, semantic HTML, `useSeoMeta`
- **Desain**: Hero gradient, section fitur, untuk siapa, CTA, footer
- **Responsif**: Mobile-first dengan Tailwind CSS
- **Nuxt 3**: Server-side rendering / static generate

## Setup

```bash
cd landing
npm install
```

## Development

```bash
npm run dev
```

Buka http://localhost:3000

## Build (Production)

```bash
npm run build
npm run preview
```

## Static Generate (untuk hosting statis)

```bash
npm run generate
```

Output di folder `.output/public`.

## Konfigurasi

- **Link "Masuk" ke aplikasi utama**: Set env `NUXT_PUBLIC_APP_URL` ke URL aplikasi POS (Vue). Contoh: `https://app.example.com` atau `http://localhost:5173`. Jika tidak diset, tombol "Masuk" akan mengarah ke path relatif `/login` (same origin).

Contoh `.env`:

```
NUXT_PUBLIC_APP_URL=https://app.pos-anda.com
```

## Deploy

- **Node (SSR)**: Jalankan `npm run build` lalu `node .output/server/index.mjs`
- **Static**: Jalankan `npm run generate`, deploy isi `.output/public` ke CDN/static host (Vercel, Netlify, dll.)
