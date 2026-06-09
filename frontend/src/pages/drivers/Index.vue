<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Data Sopir</h1>
      <button @click="openModal()" class="btn btn-primary" v-if="hasPermission('create_drivers')">
        <PlusIcon class="w-5 h-5 mr-2" />
        Tambah Sopir
      </button>
    </div>

    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input v-model="filters.search" @input="onSearchInput" type="text" placeholder="Cari nama, no ktp, atau telepon..." class="input" />
        <select v-model="filters.per_page" @change="loadDrivers(1)" class="input">
          <option :value="10">10 per halaman</option>
          <option :value="25">25 per halaman</option>
          <option :value="50">50 per halaman</option>
          <option :value="100">100 per halaman</option>
        </select>
      </div>
    </div>

    <div class="card overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <p class="mt-2 text-gray-600">Memuat data...</p>
      </div>

      <div v-else-if="!drivers.data || drivers.data.length === 0" class="p-8 text-center">
        <p class="text-gray-600 mb-4">Belum ada data sopir.</p>
        <button @click="openModal()" class="btn btn-primary" v-if="hasPermission('create_drivers')">
          <PlusIcon class="w-5 h-5 mr-2" />
          Tambah Sopir Pertama
        </button>
      </div>

      <div v-else>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="whitespace-nowrap">No KTP</th>
                <th class="whitespace-nowrap">Nama</th>
                <th class="whitespace-nowrap">Telepon</th>
                <th class="whitespace-nowrap">Alamat</th>
                <th class="whitespace-nowrap">Foto</th>
                <th class="whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in drivers.data" :key="d.id">
                <td class="font-medium">{{ d.no_ktp }}</td>
                <td>{{ d.name }}</td>
                <td>{{ d.phone || '-' }}</td>
                <td class="text-sm max-w-xs truncate" :title="d.address">{{ d.address || '-' }}</td>
                <td>
                  <div class="flex gap-2">
                    <a v-if="d.ktp_photo" :href="getImageUrl(d.ktp_photo)" target="_blank" class="text-xs text-blue-600 hover:underline">KTP</a>
                    <a v-if="d.photo" :href="getImageUrl(d.photo)" target="_blank" class="text-xs text-blue-600 hover:underline">Profil</a>
                    <span v-if="!d.ktp_photo && !d.photo" class="text-gray-400 text-xs">-</span>
                  </div>
                </td>
                <td>
                  <div class="flex gap-2">
                    <button v-if="hasPermission('edit_drivers')" @click="openModal(d)" class="text-blue-600 hover:text-blue-800" title="Edit">
                      <PencilIcon class="w-5 h-5" />
                    </button>
                    <button v-if="hasPermission('delete_drivers')" @click="confirmDelete(d)" class="text-red-600 hover:text-red-800" title="Hapus">
                      <TrashIcon class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="drivers.last_page > 1" class="px-4 py-3 border-t flex flex-wrap items-center justify-between gap-2">
          <div class="text-sm text-gray-600">
            Menampilkan {{ (drivers.current_page - 1) * drivers.per_page + 1 }} -
            {{ Math.min(drivers.current_page * drivers.per_page, drivers.total) }} dari {{ drivers.total }} data
          </div>
          <div class="flex items-center gap-2">
            <select v-model="filters.per_page" @change="loadDrivers(1)" class="input py-1.5 text-sm w-20">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="text-sm text-gray-500">per halaman</span>
            <div class="flex gap-1 ml-2">
              <button type="button" @click="loadDrivers(drivers.current_page - 1)" :disabled="drivers.current_page <= 1"
                class="btn btn-sm btn-outline py-1 px-2">&laquo;</button>
              <button v-for="p in paginationPages" :key="p" type="button"
                @click="p !== '...' && loadDrivers(p)"
                :class="['btn btn-sm py-1 px-2', p === drivers.current_page ? 'btn-primary' : 'btn-outline']"
                :disabled="p === '...'">{{ p }}</button>
              <button type="button" @click="loadDrivers(drivers.current_page + 1)"
                :disabled="drivers.current_page >= drivers.last_page"
                class="btn btn-sm btn-outline py-1 px-2">&raquo;</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <DriverFormModal v-if="showModal" :driver="editingDriver"
      @close="showModal = false; editingDriver = null"
      @saved="onSaved" />

    <div v-if="deleteTarget" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="deleteTarget = null">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-bold mb-2">Hapus Sopir?</h3>
        <p class="text-gray-600 mb-4">Sopir "{{ deleteTarget.name }}" akan dihapus. Lanjutkan?</p>
        <div class="flex gap-3">
          <button type="button" @click="deleteTarget = null" class="flex-1 btn btn-secondary">Batal</button>
          <button type="button" @click="doDelete" class="flex-1 btn btn-danger" :disabled="deleting">
            {{ deleting ? 'Menghapus...' : 'Hapus' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import DriverFormModal from './DriverFormModal.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const hasPermission = (perm) => authStore.hasPermission(perm)

const getImageUrl = (path) => {
  if (!path) return '';
  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';
  return `${baseUrl}/storage/${path}`;
}

const toast = useToast()
const loading = ref(false)
const drivers = ref({ data: [] })
const showModal = ref(false)
const editingDriver = ref(null)
const deleteTarget = ref(null)
const deleting = ref(false)

const filters = ref({
  search: '',
  per_page: 25
})

let searchTimeout = null
const onSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadDrivers(1), 400)
}

const paginationPages = computed(() => {
  const cur = drivers.value.current_page || 1
  const last = drivers.value.last_page || 1
  if (last <= 7) return [...Array(last)].map((_, i) => i + 1)
  if (cur <= 4) return [1, 2, 3, 4, 5, '...', last]
  if (cur >= last - 3) return [1, '...', last - 4, last - 3, last - 2, last - 1, last]
  return [1, '...', cur - 1, cur, cur + 1, '...', last]
})

const loadDrivers = async (page) => {
  loading.value = true
  try {
    const params = { ...filters.value }
    params.page = page ?? drivers.value?.current_page ?? 1
    const response = await api.get('/drivers', { params })
    drivers.value = response.data
  } catch (error) {
    toast.error('Gagal memuat data sopir')
  } finally {
    loading.value = false
  }
}

const openModal = (driver = null) => {
  editingDriver.value = driver
  showModal.value = true
}

const onSaved = () => {
  loadDrivers()
}

const confirmDelete = (d) => {
  deleteTarget.value = d
}

const doDelete = async () => {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await api.delete(`/drivers/${deleteTarget.value.id}`)
    toast.success('Sopir berhasil dihapus')
    deleteTarget.value = null
    loadDrivers()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus sopir')
  } finally {
    deleting.value = false
  }
}

onMounted(loadDrivers)
</script>
