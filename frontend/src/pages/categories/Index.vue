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
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama</th>
              <th>Deskripsi</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="category in categories" :key="category.id">
              <td class="font-medium">{{ category.code }}</td>
              <td>{{ category.name }}</td>
              <td>{{ category.description || '-' }}</td>
              <td>
                <span :class="category.is_active ? 'badge badge-success' : 'badge badge-danger'">
                  {{ category.is_active ? 'Aktif' : 'Tidak Aktif' }}
                </span>
              </td>
              <td>
                <button v-if="hasPermission('edit_categories')" @click="openModal(category)"
                  class="text-blue-600 hover:text-blue-800">
                  <PencilIcon class="w-5 h-5" />
                </button>
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
import { PlusIcon, PencilIcon } from '@heroicons/vue/24/outline'
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

onMounted(loadCategories)
</script>
