<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Produk</h1>
      <div class="flex gap-3">
        <button v-if="selectedProducts.length > 0" @click="openBulkBarcodeModal"
          class="btn btn-secondary">
          <PrinterIcon class="w-5 h-5 mr-2" />
          Cetak Barcode ({{ selectedProducts.length }})
        </button>
        <button v-if="hasPermission('create_products')" @click="openModal()" class="btn btn-primary">
          <PlusIcon class="w-5 h-5 mr-2" />
          Tambah Produk
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadProducts" type="text" placeholder="Cari produk..." class="input" />
        <select v-model="filters.category_id" @change="loadProducts" class="input">
          <option value="">Semua Kategori</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>
        <select v-model="filters.is_active" @change="loadProducts" class="input">
          <option value="">Semua Status</option>
          <option value="1">Aktif</option>
          <option value="0">Tidak Aktif</option>
        </select>
      </div>
    </div>

    <!-- Products Table -->
    <div class="card overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <p class="mt-2 text-gray-600">Memuat produk...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!products.data || products.data.length === 0" class="p-8 text-center">
        <p class="text-gray-600 mb-2">Belum ada produk.</p>
        <p class="text-xs text-gray-400 mb-4">
          (Data: {{ products.data ? 'array with ' + products.data.length + ' items' : 'null/undefined' }})
        </p>
        <button v-if="hasPermission('create_products')" @click="openModal()" class="btn btn-primary mt-4">
          <PlusIcon class="w-5 h-5 mr-2" />
          Tambah Produk Pertama
        </button>
      </div>

      <!-- Products Table -->
      <div v-else class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="whitespace-nowrap w-12">
                <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll"
                  class="rounded border-gray-300 text-primary-600" />
              </th>
              <th class="whitespace-nowrap">Kode</th>
              <th class="whitespace-nowrap">Nama</th>
              <th class="whitespace-nowrap">Kategori</th>
              <th class="whitespace-nowrap">Satuan Dasar</th>
              <th class="whitespace-nowrap">Harga Modal</th>
              <th class="whitespace-nowrap">Multi-Satuan</th>
              <th class="whitespace-nowrap">Stok</th>
              <th class="whitespace-nowrap">Status</th>
              <th class="whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products.data" :key="product.id">
              <td class="w-12">
                <input type="checkbox" :checked="selectedProducts.some(p => p.id === product.id)"
                  @change="toggleProduct(product)" class="rounded border-gray-300 text-primary-600" />
              </td>
              <td class="font-mono text-sm">{{ product.code }}</td>
              <td>
                <div class="font-medium">{{ product.name }}</div>
                <div class="text-xs text-gray-500">{{ product.barcode || '-' }}</div>
              </td>
              <td>{{ product.category?.name || '-' }}</td>
              <td>{{ product.base_unit?.name || product.baseUnit?.name || '-' }}</td>
              <td class="font-semibold">{{ formatCurrency(product.base_price) }}</td>
              <td>
                <div class="flex flex-wrap gap-1">
                  <span v-for="pu in (product.product_units || product.productUnits || [])" :key="pu.id"
                    class="badge badge-info text-xs">
                    {{ pu.unit?.name || '-' }}
                  </span>
                </div>
              </td>
              <td>
                <span :class="product.total_stock < product.minimum_stock ? 'text-red-600 font-bold' : ''">
                  {{ formatStock(product.total_stock) }}
                </span>
              </td>
              <td>
                <span :class="product.is_active ? 'badge badge-success' : 'badge badge-danger'">
                  {{ product.is_active ? 'Aktif' : 'Tidak Aktif' }}
                </span>
              </td>
              <td>
                <div class="flex gap-2">
                  <button @click="openBarcodeModal(product)" class="text-green-600 hover:text-green-800"
                    title="Cetak Barcode">
                    <PrinterIcon class="w-5 h-5" />
                  </button>
                  <button v-if="hasPermission('edit_products')" @click="openModal(product)"
                    class="text-blue-600 hover:text-blue-800" title="Edit">
                    <PencilIcon class="w-5 h-5" />
                  </button>
                  <button v-if="hasPermission('delete_products')" @click="deleteProduct(product)"
                    class="text-red-600 hover:text-red-800" title="Hapus">
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="products.data?.length && products.links?.length" class="px-6 py-4 border-t">
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-700">
            Menampilkan {{ products.from || 0 }} - {{ products.to || 0 }} dari {{ products.total || 0 }}
          </span>
          <div class="flex gap-2">
            <button v-for="(page, index) in products.links" :key="index" @click="changePage(page?.url)"
              :disabled="!page?.url" :class="[
                'px-3 py-1 rounded text-sm',
                page?.active ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-700',
                !page?.url && 'opacity-50 cursor-not-allowed'
              ]" v-html="page?.label || ''" />
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ProductFormModal v-if="showModal" :product="selectedProduct" :categories="categories" :units="units"
      @close="closeModal" @saved="handleSaved" />

    <BarcodePrintModal v-if="showBarcodeModal" :product="selectedBarcodeProduct"
      :products="selectedBarcodeProducts" @close="closeBarcodeModal" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatStock } from '@/utils/format'
import { PlusIcon, PencilIcon, TrashIcon, PrinterIcon } from '@heroicons/vue/24/outline'
import ProductFormModal from './ProductFormModal.vue'
import BarcodePrintModal from '@/components/BarcodePrintModal.vue'

const authStore = useAuthStore()
const toast = useToast()

const products = ref({ data: [], links: [] })
const categories = ref([])
const units = ref([])
const showModal = ref(false)
const selectedProduct = ref(null)
const loading = ref(false)
const showBarcodeModal = ref(false)
const selectedBarcodeProduct = ref(null)
const selectedBarcodeProducts = ref([])
const selectedProducts = ref([])

const isAllSelected = computed(() => {
  const data = products.value.data || []
  if (data.length === 0) return false
  return data.every((p) => selectedProducts.value.some((sp) => sp.id === p.id))
})

const toggleProduct = (product) => {
  const idx = selectedProducts.value.findIndex((p) => p.id === product.id)
  if (idx >= 0) {
    selectedProducts.value = selectedProducts.value.filter((p) => p.id !== product.id)
  } else {
    selectedProducts.value = [...selectedProducts.value, product]
  }
}

const toggleSelectAll = () => {
  const data = products.value.data || []
  if (isAllSelected.value) {
    const ids = new Set(data.map((p) => p.id))
    selectedProducts.value = selectedProducts.value.filter((p) => !ids.has(p.id))
  } else {
    const existingIds = new Set(selectedProducts.value.map((p) => p.id))
    const toAdd = data.filter((p) => !existingIds.has(p.id))
    selectedProducts.value = [...selectedProducts.value, ...toAdd]
  }
}

const filters = ref({
  search: '',
  category_id: '',
  is_active: ''
})

const hasPermission = (permission) => authStore.hasPermission(permission)

const loadProducts = async () => {
  loading.value = true
  try {
    const params = { ...filters.value }
    console.log('Loading products with params:', params)
    const response = await api.get('/products', { params })
    console.log('Products API Response:', response.data)
    console.log('Products data array:', response.data.data)
    console.log('Total products:', response.data.data?.length)

    // Set products data
    products.value = response.data

    // Log first product for debugging
    if (response.data.data && response.data.data.length > 0) {
      console.log('First product sample:', response.data.data[0])
    }
  } catch (error) {
    console.error('Error loading products:', error)
    console.error('Error response:', error.response)
    toast.error('Gagal memuat produk: ' + (error.response?.data?.message || error.message))
    products.value = { data: [], links: [] }
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const response = await api.get('/categories', { params: { per_page: 100 } })
    categories.value = response.data.data
  } catch (error) {
    console.error('Error loading categories:', error)
  }
}

const loadUnits = async () => {
  try {
    const response = await api.get('/units', { params: { per_page: 100 } })
    units.value = response.data.data
  } catch (error) {
    console.error('Error loading units:', error)
  }
}

const openModal = (product = null) => {
  selectedProduct.value = product
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedProduct.value = null
}

const handleSaved = () => {
  closeModal()
  loadProducts()
}

const openBarcodeModal = (product) => {
  selectedBarcodeProduct.value = product
  selectedBarcodeProducts.value = []
  showBarcodeModal.value = true
}

const openBulkBarcodeModal = () => {
  selectedBarcodeProducts.value = [...selectedProducts.value]
  selectedBarcodeProduct.value = null
  showBarcodeModal.value = true
}

const closeBarcodeModal = () => {
  showBarcodeModal.value = false
  selectedBarcodeProduct.value = null
  selectedBarcodeProducts.value = []
}

const deleteProduct = async (product) => {
  if (!confirm(`Yakin ingin menghapus produk ${product.name}?`)) return

  try {
    await api.delete(`/products/${product.id}`)
    toast.success('Produk berhasil dihapus')
    loadProducts()
  } catch (error) {
    toast.error('Gagal menghapus produk')
  }
}

const changePage = (url) => {
  if (!url) return
  loading.value = true
  api.get(url)
    .then((response) => {
      products.value = response.data
    })
    .catch((error) => {
      console.error('Error changing page:', error)
      toast.error('Gagal memuat halaman')
    })
    .finally(() => {
      loading.value = false
    })
}

onMounted(() => {
  loadProducts()
  loadCategories()
  loadUnits()
})
</script>
