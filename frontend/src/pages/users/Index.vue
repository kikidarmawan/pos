<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Manajemen Pengguna</h1>
      <button v-if="hasPermission('create_users')" @click="openModal()" class="btn btn-primary">
        <PlusIcon class="w-5 h-5 mr-2" />
        Tambah Pengguna
      </button>
    </div>

    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input v-model="filters.search" @input="onSearchInput" type="text" placeholder="Cari nama atau email..." class="input" />
        <select v-model="filters.per_page" @change="loadUsers(1)" class="input">
          <option :value="15">15 per halaman</option>
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

      <div v-else-if="!users.data || users.data.length === 0" class="p-8 text-center">
        <p class="text-gray-600 mb-4">Belum ada data pengguna.</p>
        <button v-if="hasPermission('create_users')" @click="openModal()" class="btn btn-primary">
          <PlusIcon class="w-5 h-5 mr-2" />
          Tambah Pengguna Pertama
        </button>
      </div>

      <div v-else>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="whitespace-nowrap">Nama</th>
                <th class="whitespace-nowrap">Email</th>
                <th class="whitespace-nowrap">Role</th>
                <th class="whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in users.data" :key="u.id">
                <td class="font-medium">{{ u.name }}</td>
                <td>{{ u.email }}</td>
                <td>
                  <span v-for="r in (u.roles || [])" :key="r.id" class="badge badge-secondary mr-1">{{ r.name }}</span>
                  <span v-if="!(u.roles?.length)" class="text-gray-400">-</span>
                </td>
                <td>
                  <div class="flex gap-2">
                    <button v-if="hasPermission('edit_users')" @click="openModal(u)" class="text-blue-600 hover:text-blue-800" title="Edit">
                      <PencilIcon class="w-5 h-5" />
                    </button>
                    <button v-if="hasPermission('delete_users')" @click="confirmDelete(u)" class="text-red-600 hover:text-red-800" title="Hapus">
                      <TrashIcon class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="users.last_page > 1" class="px-4 py-3 border-t flex flex-wrap items-center justify-between gap-2">
          <div class="text-sm text-gray-600">
            Menampilkan {{ (users.current_page - 1) * users.per_page + 1 }} -
            {{ Math.min(users.current_page * users.per_page, users.total) }} dari {{ users.total }} data
          </div>
          <div class="flex items-center gap-2">
            <select v-model="filters.per_page" @change="loadUsers(1)" class="input py-1.5 text-sm w-20">
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="text-sm text-gray-500">per halaman</span>
            <div class="flex gap-1 ml-2">
              <button type="button" @click="loadUsers(users.current_page - 1)" :disabled="users.current_page <= 1"
                class="btn btn-sm btn-outline py-1 px-2">&laquo;</button>
              <button v-for="p in paginationPages" :key="p" type="button"
                @click="p !== '...' && loadUsers(p)"
                :class="['btn btn-sm py-1 px-2', p === users.current_page ? 'btn-primary' : 'btn-outline']"
                :disabled="p === '...'">{{ p }}</button>
              <button type="button" @click="loadUsers(users.current_page + 1)"
                :disabled="users.current_page >= users.last_page"
                class="btn btn-sm btn-outline py-1 px-2">&raquo;</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <UserFormModal v-if="showModal" :user="selectedUser" @close="showModal = false; selectedUser = null" @saved="handleSaved" />

    <div v-if="deleteTarget" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="deleteTarget = null">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-bold mb-2">Hapus Pengguna?</h3>
        <p class="text-gray-600 mb-4">Pengguna "{{ deleteTarget.name }}" akan dihapus. Lanjutkan?</p>
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
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import UserFormModal from './UserFormModal.vue'

const authStore = useAuthStore()
const toast = useToast()
const loading = ref(false)
const users = ref({ data: [] })
const showModal = ref(false)
const selectedUser = ref(null)
const deleteTarget = ref(null)
const deleting = ref(false)

const filters = ref({
  search: '',
  per_page: 15
})

const hasPermission = (permission) => authStore.hasPermission(permission)

let searchTimeout = null
const onSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadUsers(1), 400)
}

const paginationPages = computed(() => {
  const cur = users.value.current_page || 1
  const last = users.value.last_page || 1
  if (last <= 7) return [...Array(last)].map((_, i) => i + 1)
  if (cur <= 4) return [1, 2, 3, 4, 5, '...', last]
  if (cur >= last - 3) return [1, '...', last - 4, last - 3, last - 2, last - 1, last]
  return [1, '...', cur - 1, cur, cur + 1, '...', last]
})

const loadUsers = async (page) => {
  loading.value = true
  try {
    const params = { ...filters.value }
    params.page = page ?? users.value?.current_page ?? 1
    const response = await api.get('/users', { params })
    users.value = response.data
  } catch (error) {
    toast.error('Gagal memuat data pengguna')
  } finally {
    loading.value = false
  }
}

const openModal = (user = null) => {
  selectedUser.value = user
  showModal.value = true
}

const handleSaved = () => {
  loadUsers()
}

const confirmDelete = (u) => {
  deleteTarget.value = u
}

const doDelete = async () => {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await api.delete(`/users/${deleteTarget.value.id}`)
    toast.success('Pengguna berhasil dihapus')
    deleteTarget.value = null
    loadUsers()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus pengguna')
  } finally {
    deleting.value = false
  }
}

onMounted(loadUsers)
</script>
