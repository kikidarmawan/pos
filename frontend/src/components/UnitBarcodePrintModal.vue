<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
        <h2 class="text-2xl font-bold">Cetak Label Kode Satuan</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div class="p-6">
        <!-- Settings -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div>
            <label class="label">Tipe Label</label>
            <select v-model="settings.type" class="input">
              <option value="barcode-only">Barcode Saja</option>
              <option value="label-small">Label Kecil (5x3 cm)</option>
              <option value="label-medium">Label Sedang (7x5 cm)</option>
              <option value="label-large">Label Besar (10x7 cm)</option>
            </select>
          </div>

          <div>
            <label class="label">Jumlah Copy</label>
            <input v-model.number="settings.copies" type="number" min="1" max="100" class="input" />
          </div>

          <div>
            <label class="label">Format Barcode</label>
            <select v-model="settings.format" class="input">
              <option value="CODE128">CODE128</option>
              <option value="EAN13">EAN13</option>
              <option value="CODE39">CODE39</option>
            </select>
          </div>
        </div>

        <!-- Options -->
        <div class="flex gap-4 mb-6">
          <label class="flex items-center">
            <input v-model="settings.showPrice" type="checkbox" class="mr-2" />
            <span class="text-sm">Tampilkan Harga</span>
          </label>
          <label class="flex items-center">
            <input v-model="settings.showName" type="checkbox" class="mr-2" />
            <span class="text-sm">Tampilkan Nama Produk</span>
          </label>
          <label class="flex items-center">
            <input v-model="settings.showCode" type="checkbox" class="mr-2" />
            <span class="text-sm">Tampilkan Kode Produk</span>
          </label>
        </div>

        <!-- Preview -->
        <div class="border rounded-lg p-4 mb-6 bg-gray-50">
          <h3 class="font-bold mb-4">Preview ({{ totalLabels }} label):</h3>
          <div id="print-area" class="grid gap-4" :class="getGridClass()">
            <template v-for="(u, uIdx) in displayUnits" :key="u.id || uIdx">
              <div v-for="i in settings.copies" :key="`${u.id}-${i}`" :class="getLabelClass()">
                <!-- Barcode Only -->
                <div v-if="settings.type === 'barcode-only'"
                  class="flex flex-col items-center justify-center p-2 bg-white">
                  <svg :id="`barcode-${uIdx}-${i}`" class="barcode"></svg>
                </div>

                <!-- Labels -->
                <div v-else class="bg-white border border-gray-300 p-2 flex flex-col items-center justify-center gap-0.5">
                  <div v-if="settings.showName" class="text-center font-bold text-sm truncate w-full leading-tight">
                    {{ u.name }}
                  </div>
                  <div v-if="settings.showCode" class="text-center text-xs text-gray-600 w-full leading-tight">
                    {{ u.code }}
                  </div>
                  <div class="flex items-center justify-center">
                    <svg :id="`barcode-${uIdx}-${i}`" class="barcode"></svg>
                  </div>
                  <div v-if="settings.showPrice" class="text-center font-bold text-primary-600 text-sm w-full leading-tight">
                    {{ formatCurrency(u.price) }}<template v-if="u.unitCode"> / {{ u.unitCode }}</template>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
          <button @click="$emit('close')" class="btn btn-secondary">
            Batal
          </button>
          <button @click="print" :disabled="printing" class="btn btn-primary">
            <PrinterIcon class="w-5 h-5 mr-2" />
            {{ isTauri() ? 'Simpan ke PDF' : 'Cetak' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useToast } from 'vue-toastification'
import { XMarkIcon, PrinterIcon } from '@heroicons/vue/24/outline'
import JsBarcode from 'jsbarcode'
import html2pdf from 'html2pdf.js'
import { formatCurrency } from '@/utils/format'
import { isTauri } from '@/utils/printReceipt'

const toast = useToast()

const props = defineProps({
  product: {
    type: Object,
    default: null
  },
  products: {
    type: Array,
    default: () => []
  }
})

const isBulkMode = computed(() => props.products?.length > 0)
const displayProducts = computed(() => {
  if (isBulkMode.value) return props.products
  return props.product ? [props.product] : []
})

const displayUnits = computed(() => {
  const units = []
  const prods = displayProducts.value
  prods.forEach(p => {
    const pUnits = p.product_units || p.productUnits || []
    pUnits.forEach(pu => {
      units.push({
        id: `${p.id}-${pu.id}`,
        name: p.name,
        unitCode: pu.unit?.code || pu.unit?.name || '',
        code: pu.barcode || p.code || '0000000000',
        price: pu.selling_price || 0
      })
    })
  })
  return units
})

const emit = defineEmits(['close'])

const settings = ref({
  type: 'label-medium',
  copies: 1,
  format: 'CODE128',
  showPrice: true,
  showName: true,
  showCode: true
})

const getGridClass = () => {
  switch (settings.value.type) {
    case 'barcode-only':
      return 'grid-cols-3'
    case 'label-small':
      return 'grid-cols-4'
    case 'label-medium':
      return 'grid-cols-3'
    case 'label-large':
      return 'grid-cols-2'
    default:
      return 'grid-cols-3'
  }
}

const getLabelClass = () => {
  const base = 'rounded overflow-hidden'
  switch (settings.value.type) {
    case 'barcode-only':
      return `${base} h-16`
    case 'label-small':
      return `${base} h-32`
    case 'label-medium':
      return `${base} h-40`
    case 'label-large':
      return `${base} h-56`
    default:
      return `${base} h-40`
  }
}

const totalLabels = computed(() =>
  displayUnits.value.length * settings.value.copies
)

// Removed getDisplayPrice as price is directly computed in displayUnits

const generateBarcodes = async () => {
  await nextTick()

  const units = displayUnits.value
  const opts = {
    format: settings.value.format,
    width: 2,
    height: settings.value.type === 'barcode-only' ? 40 : 50,
    displayValue: settings.value.type === 'barcode-only',
    fontSize: 12,
    margin: 0
  }

  for (let uIdx = 0; uIdx < units.length; uIdx++) {
    const u = units[uIdx]
    const barcodeValue = u.code

    for (let i = 1; i <= settings.value.copies; i++) {
      const el = document.getElementById(`barcode-${uIdx}-${i}`)
      if (el) {
        try {
          JsBarcode(el, barcodeValue, opts)
        } catch (error) {
          console.error('Error generating barcode:', error)
          try {
            JsBarcode(el, '0000000000', { ...opts, format: 'CODE128' })
          } catch (e) {
            console.error('Fallback barcode generation failed:', e)
          }
        }
      }
    }
  }
}

const PRINT_ZONE_ID = 'barcode-print-zone'
const PRINT_STYLE_ID = 'barcode-print-style'
const printing = ref(false)

const cols = () => getGridClass().replace('grid-cols-', '') || '3'

const print = async () => {
  if (displayUnits.value.length === 0) return

  const printArea = document.getElementById('print-area')
  if (!printArea) return

  const numCols = cols()
  const printContent = printArea.innerHTML

  // Di Tauri: simpan langsung ke PDF (unduh)
  if (isTauri()) {
    printing.value = true
    try {
      const filename = `barcode-label-${new Date().toISOString().slice(0, 19).replace(/[-:T]/g, '')}.pdf`
      await html2pdf().set({
        margin: 8,
        filename,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
      }).from(printArea).save()
      toast.success('PDF berhasil disimpan')
      emit('close')
    } catch (e) {
      console.error('Barcode PDF failed:', e)
      toast.error('Gagal menyimpan PDF')
    } finally {
      printing.value = false
    }
    return
  }

  // Browser: window.print()
  printInCurrentWindow(printContent, numCols)
}

/** Cetak di jendela saat ini (append zone ke body, lalu window.print) */
function printInCurrentWindow(printContent, numCols) {
  document.getElementById(PRINT_ZONE_ID)?.remove()
  document.getElementById(PRINT_STYLE_ID)?.remove()

  const zone = document.createElement('div')
  zone.id = PRINT_ZONE_ID
  zone.className = 'grid gap-4 ' + getGridClass()
  zone.style.cssText = `display: grid; gap: 8px; grid-template-columns: repeat(${numCols}, 1fr);`
  zone.innerHTML = printContent

  const style = document.createElement('style')
  style.id = PRINT_STYLE_ID
  style.textContent = `
    @media screen { #${PRINT_ZONE_ID} { display: none !important; } }
    @media print {
      body * { visibility: hidden !important; }
      #${PRINT_ZONE_ID}, #${PRINT_ZONE_ID} * { visibility: visible !important; }
      #${PRINT_ZONE_ID} {
        position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important;
        display: grid !important; grid-template-columns: repeat(${numCols}, 1fr) !important;
        gap: 8px !important; padding: 10mm; background: white;
      }
      #${PRINT_ZONE_ID} .barcode { max-width: 100%; height: auto; }
    }
  `
  document.body.appendChild(zone)
  document.head.appendChild(style)

  const cleanup = () => {
    document.getElementById(PRINT_ZONE_ID)?.remove()
    document.getElementById(PRINT_STYLE_ID)?.remove()
    window.onafterprint = null
  }
  window.onafterprint = cleanup
  window.print()
}

onMounted(() => {
generateBarcodes()
})

watch([settings, displayUnits], () => {
  generateBarcodes()
}, { deep: true })
</script>

<style scoped>
.barcode {
  max-width: 100%;
  height: auto;
}
</style>
