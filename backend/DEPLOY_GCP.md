# Deploy Backend ke Google Cloud Platform (GCP)

Backend Laravel ini siap di-deploy ke **Google Cloud Run** (container tanpa server). Dockerfile sudah disesuaikan dengan GCP (menggunakan env `PORT`).

---

## Yang sudah disesuaikan untuk GCP

- **PORT**: Aplikasi mendengarkan di `PORT` dari environment (Cloud Run menyetel `PORT=8080`). Lokal tetap bisa pakai `docker run -p 8000:8000 -e PORT=8000 ...`.
- **Entrypoint**: `docker-entrypoint.sh` membaca `PORT` lalu menjalankan `php artisan serve --host=0.0.0.0 --port=$PORT`.

---

## Prasyarat

1. Akun Google Cloud dengan billing aktif.
2. **Google Cloud SDK** (`gcloud`) terpasang dan sudah login: `gcloud auth login`.
3. Project GCP: `gcloud config set project YOUR_PROJECT_ID`

---

## Opsi 1: Cloud Run (disarankan)

### 1. Build & push image ke Artifact Registry

```bash
# Buat repo (sekali saja)
gcloud artifacts repositories create pos-repo --repository-format=docker --location=asia-southeast2

# Konfigurasi Docker untuk auth
gcloud auth configure-docker asia-southeast2-docker.pkg.dev

# Build (dari folder backend)
cd backend
docker build -t asia-southeast2-docker.pkg.dev/YOUR_PROJECT_ID/pos-repo/pos-backend:latest .

# Push
docker push asia-southeast2-docker.pkg.dev/YOUR_PROJECT_ID/pos-repo/pos-backend:latest
```

Ganti `YOUR_PROJECT_ID` dengan ID project GCP Anda. Ganti `asia-southeast2` jika pakai region lain.

### 2. Database: Cloud SQL (MySQL)

- Buat instance Cloud SQL (MySQL 8) di konsol GCP.
- Catat **Connection name** (mis. `project:region:instance`) dan **password** user root.
- Untuk Cloud Run ke Cloud SQL: saat deploy Cloud Run, aktifkan **Cloud SQL connection** dan pilih instance tersebut.

### 3. Deploy ke Cloud Run

```bash
gcloud run deploy pos-backend \
  --image asia-southeast2-docker.pkg.dev/YOUR_PROJECT_ID/pos-repo/pos-backend:latest \
  --region asia-southeast2 \
  --platform managed \
  --allow-unauthenticated \
  --set-env-vars "APP_ENV=production" \
  --set-env-vars "APP_DEBUG=false" \
  --set-env-vars "APP_KEY=base64:YOUR_APP_KEY" \
  --set-env-vars "DB_CONNECTION=mysql" \
  --set-env-vars "DB_HOST=/cloudsql/INSTANCE_CONNECTION_NAME" \
  --set-env-vars "DB_DATABASE=pos" \
  --set-env-vars "DB_USERNAME=root" \
  --set-env-vars "DB_PASSWORD=YOUR_DB_PASSWORD" \
  --add-cloudsql-instances INSTANCE_CONNECTION_NAME
```

- Ganti `YOUR_APP_KEY`: hasil `php artisan key:generate` (atau dari `.env`).
- Ganti `INSTANCE_CONNECTION_NAME` dengan Connection name Cloud SQL (format: `project:region:instance`).
- Ganti `YOUR_DB_PASSWORD` dan sesuaikan `DB_DATABASE`, `DB_USERNAME` jika perlu.

**Tanpa Cloud SQL (pakai MySQL di tempat lain):**

- Hapus `--add-cloudsql-instances` dan `DB_HOST=/cloudsql/...`.
- Set `DB_HOST=IP_ATAU_HOST_MYSQL` dan pastikan Cloud Run bisa akses (VPC / IP publik).

### 4. Migrasi database (sekali setelah deploy)

Jalankan migrasi dari lokal (dengan koneksi ke Cloud SQL) atau lewat Cloud Run Job / Cloud Shell:

```bash
# Dari Cloud Shell atau mesin yang bisa akses Cloud SQL
gcloud run jobs create pos-migrate --image asia-southeast2-docker.pkg.dev/.../pos-backend:latest \
  --region asia-southeast2 --set-env-vars "..." --add-cloudsql-instances ...
# Lalu override command: php artisan migrate --force
```

Atau sambungkan IP publik Cloud SQL dan jalankan `php artisan migrate --force` dari laptop (pastikan IP Anda di-allow di Cloud SQL).

---

## Opsi 2: Build di GCP dengan Cloud Build

Supaya build jalan di GCP (tanpa build di laptop):

```bash
cd backend
gcloud builds submit --tag asia-southeast2-docker.pkg.dev/YOUR_PROJECT_ID/pos-repo/pos-backend:latest
```

Lalu deploy ke Cloud Run seperti langkah 3 di atas.

---

## Variabel environment penting di Cloud Run

| Variabel       | Contoh / Keterangan |
|----------------|---------------------|
| `APP_KEY`      | Wajib. Dari `php artisan key:generate` |
| `APP_ENV`      | `production` |
| `APP_DEBUG`    | `false` |
| `APP_URL`      | URL layanan Cloud Run (mis. `https://pos-backend-xxx.run.app`) |
| `DB_CONNECTION`| `mysql` |
| `DB_HOST`      | `/cloudsql/CONNECTION_NAME` (Cloud SQL) atau IP/host MySQL |
| `DB_DATABASE`  | Nama database |
| `DB_USERNAME`  | User database |
| `DB_PASSWORD`  | Password (simpan di Secret Manager lebih aman) |
| `CORS / SANCTUM` | Sesuaikan dengan URL frontend jika pakai SPA |

Untuk rahasia (mis. `DB_PASSWORD`, `APP_KEY`), sebaiknya pakai **Secret Manager** lalu referensi di Cloud Run:  
`--set-secrets="DB_PASSWORD=db-password:latest"`

---

## Ringkasan

- Dockerfile + entrypoint sudah **siap GCP**: aplikasi mendengarkan di `PORT` (Cloud Run).
- Deploy layanan: **Cloud Run** + image di **Artifact Registry**.
- Database: **Cloud SQL (MySQL)**; atur env dan (jika pakai) Cloud SQL connection.
- Set `APP_KEY`, `DB_*`, dan (opsional) `APP_URL`; migrasi dijalankan terpisah (sekali).

Setelah deploy, URL layanan akan seperti: `https://pos-backend-xxxxx-asia-southeast2.run.app`. Gunakan URL ini sebagai `APP_URL` dan untuk akses API dari frontend.
