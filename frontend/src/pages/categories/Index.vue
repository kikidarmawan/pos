<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Kategori Produk</h1>
      <button v-if="hasPermission('create_categories')" @click="openModal()" class="btn btn-primary">
        <PlusIcon class="w-5 h-5 mr-2" />
        Tambah Kategori
      </button>
    </div>

    <div class="card overflow-hidden">
      <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="w-full text-sm text-left border-collapse">
          <thead class="bg-gray-50 text-gray-600 text-xs uppercase sticky top-0 shadow-sm z-10">
            <tr>
              <th class="px-4 py-2.5 border-b font-semibold">Kode</th>
              <th class="px-4 py-2.5 border-b font-semibold">Nama</th>
              <th class="px-4 py-2.5 border-b font-semibold">Deskripsi</th>
              <th class="px-4 py-2.5 border-b font-semibold">Status</th>
              <th class="px-4 py-2.5 border-b font-semibold">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-2.5 font-medium">{{ category.code }}</td>
              <td class="px-4 py-2.5">{{ category.name }}</td>
              <td class="px-4 py-2.5">{{ category.description || '-' }}</td>
              <td class="px-4 py-2.5">
                <span :class="category.is_active ? 'badge badge-success' : 'badge badge-danger'">
                  {{ category.is_active ? 'Aktif' : 'Tidak Aktif' }}
                </span>
              </td>
              <td class="px-4 py-2.5">
                <div class="flex gap-2">
                  <button v-if="hasPermission('edit_categories')" @click="openModal(category)"
                    class="text-blue-600 hover:text-blue-800" title="Edit">
                    <PencilIcon class="w-5 h-5" />
                  </button>
                  <button v-if="hasPermission('delete_categories')" @click="deleteCategory(category)"
                    class="text-red-600 hover:text-red-800" title="Hapus">
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <CategoryFormModal v-if="showModal" :category="selectedCategory" @close="showModal = false" @saved="handleSaved" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import CategoryFormModal from './CategoryFormModal.vue'

const authStore = useAuthStore()
const toast = useToast()
const categories = ref([])
const showModal = ref(false)
const selectedCategory = ref(null)

const hasPermission = (permission) => authStore.hasPermission(permission)

const loadCategories = async () => {
  try {
    const response = await api.get('/categories', { params: { per_page: 100 } })
    categories.value = response.data.data
  } catch (error) {
    toast.error('Gagal memuat kategori')
  }
}

const openModal = (category = null) => {
  selectedCategory.value = category
  showModal.value = true
}

const handleSaved = () => {
  loadCategories()
}

const deleteCategory = async (category) => {
  if (!confirm(`Yakin ingin menghapus kategori ${category.name}?`)) return
  
  try {
    await api.delete(`/categories/${category.id}`)
    toast.success('Kategori berhasil dihapus')
    loadCategories()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus kategori')
  }
}

onMounted(loadCategories)
</script>
