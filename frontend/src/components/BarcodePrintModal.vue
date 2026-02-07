<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
        <h2 class="text-2xl font-bold">Cetak Barcode / Label</h2>
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
            <template v-for="(p, pIdx) in displayProducts" :key="p.id || pIdx">
              <div v-for="i in settings.copies" :key="`${p.id}-${i}`" :class="getLabelClass()">
                <!-- Barcode Only -->
                <div v-if="settings.type === 'barcode-only'"
                  class="flex flex-col items-center justify-center p-2 bg-white">
                  <svg :id="`barcode-${pIdx}-${i}`" class="barcode"></svg>
                </div>

                <!-- Labels -->
                <div v-else class="bg-white border border-gray-300 p-3 flex flex-col">
                  <div v-if="settings.showName" class="text-center font-bold mb-1 text-sm truncate">
                    {{ p.name }}
                  </div>
                  <div v-if="settings.showCode" class="text-center text-xs text-gray-600 mb-2">
                    {{ p.code }}
                  </div>
                  <div class="flex-1 flex items-center justify-center">
                    <svg :id="`barcode-${pIdx}-${i}`" class="barcode"></svg>
                  </div>
                  <div v-if="settings.showPrice" class="text-center font-bold text-primary-600 mt-2">
                    {{ formatCurrency(getDisplayPrice(p)) }}
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
          <button @click="print" class="btn btn-primary">
            <PrinterIcon class="w-5 h-5 mr-2" />
            Cetak
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { XMarkIcon, PrinterIcon } from '@heroicons/vue/24/outline'
import JsBarcode from 'jsbarcode'
import { formatCurrency } from '@/utils/format'

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
  displayProducts.value.length * settings.value.copies
)

const getDisplayPrice = (product) => {
  const p = product || props.product
  if (!p) return 0
  const defaultUnit = p.product_units?.find(u => u.is_default) ||
    p.productUnits?.find(u => u.is_default)
  return defaultUnit?.selling_price || p.base_price || 0
}

const generateBarcodes = async () => {
  await nextTick()

  const prods = displayProducts.value
  const opts = {
    format: settings.value.format,
    width: 2,
    height: settings.value.type === 'barcode-only' ? 40 : 60,
    displayValue: settings.value.type === 'barcode-only',
    fontSize: 12,
    margin: 5
  }

  for (let pIdx = 0; pIdx < prods.length; pIdx++) {
    const p = prods[pIdx]
    const barcodeValue = p.barcode || p.code || '0000000000'

    for (let i = 1; i <= settings.value.copies; i++) {
      const el = document.getElementById(`barcode-${pIdx}-${i}`)
      if (el) {
        try {
          JsBarcode(el, barcodeValue, opts)
        } catch (error) {
          console.error('Error generating barcode:', error)
          try {
            JsBarcode(el, p.code || '0000000000', { ...opts, format: 'CODE128' })
          } catch (e) {
            console.error('Fallback barcode generation failed:', e)
          }
        }
      }
    }
  }
}

const print = () => {
  if (displayProducts.value.length === 0) return
  const printContent = document.getElementById('print-area')?.innerHTML || ''
  const printWindow = window.open('', '_blank')

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <title>Cetak Barcode - ${isBulkMode.value ? displayProducts.value.length + ' Produk' : (props.product?.name || '')}</title>
        <style>
          @media print {
            @page {
              margin: 10mm;
              size: auto;
            }
            body {
              margin: 0;
              padding: 0;
            }
          }
          body {
            font-family: Arial, sans-serif;
          }
          .grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(${getGridClass().replace('grid-cols-', '')}, 1fr);
          }
          .label {
            page-break-inside: avoid;
            break-inside: avoid;
          }
          ${settings.value.type === 'barcode-only' ? `
            .label {
              height: 60px;
              display: flex;
              align-items: center;
              justify-content: center;
              background: white;
            }
          ` : `
            .label {
              border: 1px solid #ccc;
              padding: 8px;
              background: white;
              display: flex;
              flex-direction: column;
              ${settings.value.type === 'label-small' ? 'min-height: 120px;' : ''}
              ${settings.value.type === 'label-medium' ? 'min-height: 160px;' : ''}
              ${settings.value.type === 'label-large' ? 'min-height: 220px;' : ''}
            }
          `}
          .barcode {
            max-width: 100%;
            height: auto;
          }
          .text-center {
            text-align: center;
          }
          .font-bold {
            font-weight: bold;
          }
          .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
          }
          .price {
            color: #2563eb;
            font-size: 14px;
            font-weight: bold;
            margin-top: 8px;
          }
          .code {
            font-size: 10px;
            color: #666;
            margin-bottom: 4px;
          }
          .name {
            font-size: 12px;
            margin-bottom: 4px;
          }
        </style>
      </head>
      <body>
        <div class="grid">
          ${printContent}
        </div>
        <script>
          window.onload = function() {
            window.print();
            window.onafterprint = function() {
              window.close();
            }
          }
        <\/script>
</body>

</html>
`)

printWindow.document.close()
}

onMounted(() => {
generateBarcodes()
})

watch([settings, displayProducts], () => {
  generateBarcodes()
}, { deep: true })
</script>

<style scoped>
.barcode {
  max-width: 100%;
  height: auto;
}
</style>
