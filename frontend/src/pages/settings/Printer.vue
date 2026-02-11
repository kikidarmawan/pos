<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Pengaturan Printer</h1>
    <p class="text-gray-600 mb-6">
      Pengaturan ini dipakai saat cetak struk dari aplikasi desktop (Tauri). Jika Anda memilih "Printer untuk struk" di bawah, struk akan langsung dicetak ke printer tersebut; jika tidak, akan muncul dialog print.
    </p>

    <div class="max-w-2xl space-y-6">
      <!-- Scan device printer -->
      <div class="card">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Printer yang terdeteksi</h2>
        <p class="text-sm text-gray-600 mb-4">
          Klik "Scan printer" untuk mendeteksi printer yang terpasang di komputer (hanya di aplikasi desktop). Termasuk printer Bluetooth yang sudah dipasang (pairing) di Pengaturan Sistem.
        </p>
        <template v-if="isTauri()">
          <button type="button" @click="scanPrinters" :disabled="scanning" class="btn btn-secondary mb-4">
            {{ scanning ? 'Memindai...' : 'Scan printer' }}
          </button>
          <div v-if="scanError" class="text-sm text-red-600 mb-2">{{ scanError }}</div>
          <div v-if="printers.length > 0" class="space-y-3">
            <div class="flex items-center gap-3">
              <label class="label shrink-0">Printer untuk struk:</label>
              <select v-model="form.defaultPrinterName" class="input flex-1 max-w-md">
                <option :value="null">Tidak dipilih (gunakan dialog print)</option>
                <option v-for="name in printerOptions" :key="name" :value="name">{{ name }}{{ printers.includes(name) ? '' : ' (belum di-scan)' }}</option>
              </select>
            </div>
            <p class="text-sm text-gray-500">Jika dipilih, struk akan langsung dicetak ke printer ini saat penjualan/POS. Jika tidak dipilih, akan muncul dialog print.</p>
            <div class="border border-gray-200 rounded-lg divide-y divide-gray-200 max-h-60 overflow-y-auto">
              <div v-for="(name, i) in printers" :key="i" class="px-4 py-2.5 text-sm text-gray-700 flex items-center gap-2">
                <span class="w-5 h-5 rounded bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-medium">{{ i + 1 }}</span>
                {{ name }}
              </div>
            </div>
          </div>
          <p v-else-if="!scanning && lastScanned" class="text-sm text-gray-500">Tidak ada printer terdeteksi. Tambah printer di pengaturan sistem.</p>
        </template>
        <p v-else class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
          Fitur scan printer hanya tersedia di aplikasi desktop (Tauri). Buka aplikasi desktop lalu buka halaman ini lagi.
        </p>
      </div>

      <!-- Pengaturan struk -->
      <div class="card">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Pengaturan struk</h2>
        <form @submit.prevent="handleSave" class="space-y-6">
          <div>
            <label class="label">Lebar kertas struk</label>
            <select v-model="form.paperWidth" class="input">
              <option :value="58">58 mm (thermal kecil)</option>
              <option :value="80">80 mm (thermal standar)</option>
            </select>
            <p class="text-sm text-gray-500 mt-1">Sesuaikan dengan lebar kertas printer thermal Anda.</p>
          </div>

          <div>
            <label class="label">Ukuran font struk</label>
            <select v-model="form.fontSize" class="input">
              <option value="small">Kecil</option>
              <option value="normal">Normal</option>
              <option value="large">Besar</option>
            </select>
          </div>

          <div class="flex items-center gap-3">
            <input v-model="form.showStoreHeader" type="checkbox" id="showStoreHeader" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            <label for="showStoreHeader" class="text-sm text-gray-700">Tampilkan header toko di struk (nama, alamat, telepon)</label>
          </div>

          <div class="flex flex-wrap gap-3">
            <button type="submit" :disabled="loading" class="btn btn-primary">
              {{ loading ? 'Menyimpan...' : 'Simpan' }}
            </button>
            <button type="button" @click="handleTestPrint" :disabled="testPrinting" class="btn btn-secondary">
              {{ testPrinting ? 'Mencetak...' : 'Cetak uji' }}
            </button>
          </div>
        </form>
        <p class="text-sm text-gray-500 mt-3">Gunakan "Cetak uji" untuk memastikan pengaturan printer dan struk sudah benar.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { getPrinterSettings, savePrinterSettings, getSystemPrinters, isTauri, printTestReceipt } from '@/utils/printReceipt'

const toast = useToast()
const printerOptions = computed(() => {
  const names = [...printers.value]
  const current = form.value.defaultPrinterName
  if (current && !names.includes(current)) names.unshift(current)
  return names
})
const loading = ref(false)
const testPrinting = ref(false)
const scanning = ref(false)
const printers = ref([])
const scanError = ref('')
const lastScanned = ref(false)
const form = ref({
  paperWidth: 80,
  fontSize: 'normal',
  showStoreHeader: true,
  defaultPrinterName: null,
})

onMounted(async () => {
  try {
    const saved = await getPrinterSettings()
    form.value = {
      paperWidth: saved.paperWidth,
      fontSize: saved.fontSize,
      showStoreHeader: saved.showStoreHeader,
      defaultPrinterName: saved.defaultPrinterName ?? null,
    }
  } catch (_) {
    form.value = { ...form.value }
  }
})

const scanPrinters = async () => {
  if (!isTauri()) return
  scanning.value = true
  scanError.value = ''
  try {
    printers.value = await getSystemPrinters()
    lastScanned.value = true
    if (printers.value.length > 0) {
      toast.success(`${printers.value.length} printer terdeteksi`)
    } else {
      toast.info('Tidak ada printer terdeteksi')
    }
  } catch (e) {
    scanError.value = e.message || 'Gagal memindai printer'
    printers.value = []
    lastScanned.value = true
    toast.error(scanError.value)
  } finally {
    scanning.value = false
  }
}

const handleSave = async () => {
  loading.value = true
  try {
    await savePrinterSettings(form.value)
    toast.success('Pengaturan printer berhasil disimpan')
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Gagal menyimpan')
  } finally {
    loading.value = false
  }
}

const handleTestPrint = async () => {
  testPrinting.value = true
  try {
    await printTestReceipt(form.value)
    toast.success('Struk uji berhasil dikirim ke printer')
  } catch (e) {
    toast.error(e?.message || 'Gagal cetak uji')
  } finally {
    testPrinting.value = false
  }
}
</script>
