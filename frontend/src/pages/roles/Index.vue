<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Peran</h1>
      <button v-if="hasPermission('create_roles')" @click="openModal()" class="btn btn-primary">
        <PlusIcon class="w-5 h-5 mr-2" />
        Tambah Peran
      </button>
    </div>

    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input v-model="filters.search" @input="onSearchInput" type="text" placeholder="Cari nama peran..." class="input" />
        <select v-model="filters.per_page" @change="loadRoles(1)" class="input">
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

      <div v-else-if="!roles.data || roles.data.length === 0" class="p-8 text-center">
        <p class="text-gray-600 mb-4">Belum ada data peran.</p>
        <button v-if="hasPermission('create_roles')" @click="openModal()" class="btn btn-primary">
          <PlusIcon class="w-5 h-5 mr-2" />
          Tambah Peran Pertama
        </button>
      </div>

      <div v-else>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="whitespace-nowrap">Nama Peran</th>
                <th class="whitespace-nowrap">Hak Akses</th>
                <th class="whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in roles.data" :key="r.id">
                <td class="font-medium">{{ r.name }}</td>
                <td>
                  <span v-for="p in (r.permissions || []).slice(0, 5)" :key="p.id" class="badge badge-info mr-1 text-xs">{{ p.name }}</span>
                  <span v-if="(r.permissions || []).length > 5" class="text-gray-500 text-sm">+{{ (r.permissions || []).length - 5 }} lainnya</span>
                  <span v-if="!(r.permissions?.length)" class="text-gray-400">-</span>
                </td>
                <td>
                  <div class="flex gap-2">
                    <button v-if="hasPermission('edit_roles')" @click="openModal(r)" class="text-blue-600 hover:text-blue-800" title="Edit">
                      <PencilIcon class="w-5 h-5" />
                    </button>
                    <button v-if="hasPermission('delete_roles')" @click="confirmDelete(r)" class="text-red-600 hover:text-red-800" title="Hapus">
                      <TrashIcon class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="roles.last_page > 1" class="px-4 py-3 border-t flex flex-wrap items-center justify-between gap-2">
          <div class="text-sm text-gray-600">
            Menampilkan {{ (roles.current_page - 1) * roles.per_page + 1 }} -
            {{ Math.min(roles.current_page * roles.per_page, roles.total) }} dari {{ roles.total }} data
          </div>
          <div class="flex items-center gap-2">
            <select v-model="filters.per_page" @change="loadRoles(1)" class="input py-1.5 text-sm w-20">
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="text-sm text-gray-500">per halaman</span>
            <div class="flex gap-1 ml-2">
              <button type="button" @click="loadRoles(roles.current_page - 1)" :disabled="roles.current_page <= 1"
                class="btn btn-sm btn-outline py-1 px-2">&laquo;</button>
              <button v-for="p in paginationPages" :key="p" type="button"
                @click="p !== '...' && loadRoles(p)"
                :class="['btn btn-sm py-1 px-2', p === roles.current_page ? 'btn-primary' : 'btn-outline']"
                :disabled="p === '...'">{{ p }}</button>
              <button type="button" @click="loadRoles(roles.current_page + 1)"
                :disabled="roles.current_page >= roles.last_page"
                class="btn btn-sm btn-outline py-1 px-2">&raquo;</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <RoleFormModal v-if="showModal" :role="selectedRole" @close="showModal = false; selectedRole = null" @saved="handleSaved" />

    <div v-if="deleteTarget" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="deleteTarget = null">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-bold mb-2">Hapus Peran?</h3>
        <p class="text-gray-600 mb-4">Peran "{{ deleteTarget.name }}" akan dihapus. Lanjutkan?</p>
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
import RoleFormModal from './RoleFormModal.vue'

const authStore = useAuthStore()
const toast = useToast()
const loading = ref(false)
const roles = ref({ data: [] })
const showModal = ref(false)
const selectedRole = ref(null)
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
  searchTimeout = setTimeout(() => loadRoles(1), 400)
}

const paginationPages = computed(() => {
  const cur = roles.value.current_page || 1
  const last = roles.value.last_page || 1
  if (last <= 7) return [...Array(last)].map((_, i) => i + 1)
  if (cur <= 4) return [1, 2, 3, 4, 5, '...', last]
  if (cur >= last - 3) return [1, '...', last - 4, last - 3, last - 2, last - 1, last]
  return [1, '...', cur - 1, cur, cur + 1, '...', last]
})

const loadRoles = async (page) => {
  loading.value = true
  try {
    const params = { ...filters.value }
    params.page = page ?? roles.value?.current_page ?? 1
    const response = await api.get('/roles', { params })
    roles.value = response.data
  } catch (error) {
    toast.error('Gagal memuat data peran')
  } finally {
    loading.value = false
  }
}

const openModal = (role = null) => {
  selectedRole.value = role
  showModal.value = true
}

const handleSaved = () => {
  loadRoles()
}

const confirmDelete = (r) => {
  deleteTarget.value = r
}

const doDelete = async () => {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await api.delete(`/roles/${deleteTarget.value.id}`)
    toast.success('Peran berhasil dihapus')
    deleteTarget.value = null
    loadRoles()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus peran')
  } finally {
    deleting.value = false
  }
}

onMounted(loadRoles)
</script>
