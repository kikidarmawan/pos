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
        <button v-if="selectedProducts.length > 0" @click="openBulkUnitBarcodeModal"
          class="btn btn-secondary">
          <PrinterIcon class="w-5 h-5 mr-2" />
          Cetak Label Kode Satuan ({{ selectedProducts.length }})
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
        <input v-model="filters.search" @input="() => loadProducts(1)" type="text" placeholder="Cari produk..." class="input" />
        <select v-model="filters.category_id" @change="() => loadProducts(1)" class="input">
          <option value="">Semua Kategori</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>
        <select v-model="filters.is_active" @change="() => loadProducts(1)" class="input">
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
              <th class="whitespace-nowrap w-14">Gambar</th>
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
              <td class="w-14">
                <img v-if="product.image" :src="product.image" :alt="product.name"
                  class="w-10 h-10 object-cover rounded border border-gray-200" />
                <span v-else class="inline-flex w-10 h-10 items-center justify-center rounded bg-gray-100 text-gray-400 text-xs">—</span>
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
      <div v-if="products.data?.length && paginationMeta.total" class="px-6 py-4 border-t bg-gray-50">
        <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
          <div class="flex items-center gap-4 flex-wrap">
            <span class="text-sm text-gray-700">
              Menampilkan {{ paginationMeta.from }} - {{ paginationMeta.to }} dari {{ paginationMeta.total }} produk
            </span>
            <div class="flex items-center gap-2">
              <label class="text-sm text-gray-600">Per halaman:</label>
              <select v-model.number="perPage" @change="loadProducts(1)" class="input py-1.5 text-sm w-20">
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <button
              type="button"
              :disabled="paginationMeta.current_page <= 1"
              @click="goToPage(paginationMeta.current_page - 1)"
              class="px-3 py-1.5 rounded text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Sebelumnya
            </button>
            <template v-for="n in pageNumbers" :key="n">
              <button
                v-if="n !== '...'"
                type="button"
                @click="goToPage(n)"
                :class="[
                  'min-w-[2.25rem] px-3 py-1.5 rounded text-sm font-medium border',
                  n === paginationMeta.current_page
                    ? 'bg-primary-600 border-primary-600 text-white'
                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                ]"
              >
                {{ n }}
              </button>
              <span v-else class="px-2 py-1.5 text-gray-400">...</span>
            </template>
            <button
              type="button"
              :disabled="paginationMeta.current_page >= paginationMeta.last_page"
              @click="goToPage(paginationMeta.current_page + 1)"
              class="px-3 py-1.5 rounded text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ProductFormModal v-if="showModal" :product="selectedProduct" :categories="categories" :units="units"
      @close="closeModal" @saved="handleSaved" />

    <BarcodePrintModal v-if="showBarcodeModal" :product="selectedBarcodeProduct"
      :products="selectedBarcodeProducts" @close="closeBarcodeModal" />

    <UnitBarcodePrintModal v-if="showUnitBarcodeModal" :product="selectedBarcodeProduct"
      :products="selectedBarcodeProducts" @close="closeUnitBarcodeModal" />
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
import UnitBarcodePrintModal from '@/components/UnitBarcodePrintModal.vue'

const authStore = useAuthStore()
const toast = useToast()

const products = ref({ data: [], meta: {}, links: {} })
const categories = ref([])
const units = ref([])
const showModal = ref(false)
const selectedProduct = ref(null)
const loading = ref(false)
const showBarcodeModal = ref(false)
const showUnitBarcodeModal = ref(false)
const selectedBarcodeProduct = ref(null)
const selectedBarcodeProducts = ref([])
const selectedProducts = ref([])
const perPage = ref(15)

const paginationMeta = computed(() => ({
  current_page: products.value.meta?.current_page ?? 1,
  last_page: products.value.meta?.last_page ?? 1,
  from: products.value.meta?.from ?? 0,
  to: products.value.meta?.to ?? 0,
  total: products.value.meta?.total ?? 0,
  per_page: products.value.meta?.per_page ?? 15,
}))

const pageNumbers = computed(() => {
  const cur = paginationMeta.value.current_page
  const last = paginationMeta.value.last_page
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
  if (cur <= 4) return [1, 2, 3, 4, 5, '...', last]
  if (cur >= last - 3) return [1, '...', last - 4, last - 3, last - 2, last - 1, last]
  return [1, '...', cur - 1, cur, cur + 1, '...', last]
})

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
  is_active: '',
})

const hasPermission = (permission) => authStore.hasPermission(permission)

const loadProducts = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      ...filters.value,
      per_page: perPage.value,
      page,
    }
    const response = await api.get('/products', { params })
    products.value = response.data
  } catch (error) {
    console.error('Error loading products:', error)
    toast.error('Gagal memuat produk: ' + (error.response?.data?.message || error.message))
    products.value = { data: [], meta: {}, links: {} }
  } finally {
    loading.value = false
  }
}

const goToPage = (page) => {
  if (page < 1 || page > paginationMeta.value.last_page) return
  loadProducts(page)
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

const openBulkUnitBarcodeModal = () => {
  selectedBarcodeProducts.value = [...selectedProducts.value]
  selectedBarcodeProduct.value = null
  showUnitBarcodeModal.value = true
}

const closeUnitBarcodeModal = () => {
  showUnitBarcodeModal.value = false
  selectedBarcodeProduct.value = null
  selectedBarcodeProducts.value = []
}

const deleteProduct = async (product) => {
  if (!confirm(`Yakin ingin menghapus produk ${product.name}?`)) return

  try {
    await api.delete(`/products/${product.id}`)
    toast.success('Produk berhasil dihapus')
    const cur = paginationMeta.value.current_page
    const dataLen = products.value.data?.length ?? 0
    if (dataLen <= 1 && cur > 1) {
      loadProducts(cur - 1)
    } else {
      loadProducts(cur)
    }
  } catch (error) {
    toast.error('Gagal menghapus produk')
  }
}

onMounted(() => {
  loadProducts()
  loadCategories()
  loadUnits()
})
</script>
