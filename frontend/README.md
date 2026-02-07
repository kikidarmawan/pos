# POS System - Frontend

Frontend aplikasi POS menggunakan Vue.js 3 + Vite + Tailwind CSS.

## Features

- Vue.js 3 with Composition API
- Vite for fast development
- Tailwind CSS for styling
- Pinia for state management
- Vue Router for navigation
- Axios for API calls
- PWA support
- Modern & Responsive UI

## Installation

### 1. Install Dependencies

```bash
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
```

Edit `.env` jika perlu mengubah API URL:

```env
VITE_API_URL=http://localhost:8000
```

### 3. Start Development Server

```bash
npm run dev
```

Frontend akan berjalan di `http://localhost:5173`

### 4. Build for Production

```bash
npm run build
```

Output akan ada di folder `dist/`

## Project Structure

```
frontend/
├── src/
│   ├── assets/         # Static assets
│   ├── components/     # Reusable components
│   ├── layouts/        # Layout components
│   ├── pages/          # Page components
│   ├── router/         # Vue Router config
│   ├── stores/         # Pinia stores
│   ├── utils/          # Utility functions
│   ├── App.vue         # Root component
│   └── main.js         # Entry point
├── public/             # Public assets
└── index.html          # HTML template
```

## API Configuration

Frontend berkomunikasi dengan backend Laravel melalui:
- Base URL: `http://localhost:8000/api`
- Authentication: Bearer Token (Laravel Sanctum)
- CORS: Sudah dikonfigurasi di backend

## Default Credentials

- **Super Admin**: admin@pos.com / password
- **Admin**: admin.user@pos.com / password
- **Kasir**: kasir@pos.com / password
- **Gudang**: gudang@pos.com / password

## Development Tips

1. **Hot Module Replacement (HMR)**
   - Vite otomatis reload saat file berubah
   - Sangat cepat untuk development

2. **Proxy API**
   - Vite proxy sudah dikonfigurasi
   - Semua request `/api/*` akan di-forward ke backend

3. **PWA Testing**
   - Build production dulu: `npm run build`
   - Serve: `npm run preview`
   - Buka di Chrome, install sebagai PWA

4. **Debugging**
   - Vue DevTools extension sangat membantu
   - Install di Chrome/Firefox

## Deployment

### Build Production

```bash
npm run build
```

### Deploy ke Server

Upload folder `dist/` ke web server (Nginx, Apache, dll)

### Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        proxy_pass http://backend-server:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## Troubleshooting

**Problem: CORS Error**
- Pastikan backend Laravel CORS sudah dikonfigurasi
- Check `SANCTUM_STATEFUL_DOMAINS` di backend `.env`

**Problem: API tidak terkoneksi**
- Check `VITE_API_URL` di `.env`
- Pastikan backend Laravel running
- Check network tab di browser DevTools

**Problem: Blank page after build**
- Check base path di vite.config.js
- Pastikan assets path benar

---

Built with Vue.js 3 + Vite + Tailwind CSS
