<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Data Pelanggan</h1>
      <button @click="openModal()" class="btn btn-primary">
        <PlusIcon class="w-5 h-5 mr-2" />
        Tambah Pelanggan
      </button>
    </div>

    <div class="card mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="onSearchInput" type="text" placeholder="Cari nama, kode, atau telepon..." class="input" />
        <select v-model="filters.is_active" @change="loadCustomers(1)" class="input">
          <option value="">Semua Status</option>
          <option value="1">Aktif</option>
          <option value="0">Tidak Aktif</option>
        </select>
        <select v-model="filters.per_page" @change="loadCustomers(1)" class="input">
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

      <div v-else-if="!customers.data || customers.data.length === 0" class="p-8 text-center">
        <p class="text-gray-600 mb-4">Belum ada data pelanggan.</p>
        <button @click="openModal()" class="btn btn-primary">
          <PlusIcon class="w-5 h-5 mr-2" />
          Tambah Pelanggan Pertama
        </button>
      </div>

      <div v-else>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="whitespace-nowrap">Kode</th>
                <th class="whitespace-nowrap">Nama</th>
                <th class="whitespace-nowrap">Telepon</th>
                <th class="whitespace-nowrap">Alamat</th>
                <th class="whitespace-nowrap">Status</th>
                <th class="whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in customers.data" :key="c.id">
                <td class="font-medium">{{ c.code || '-' }}</td>
                <td>{{ c.name }}</td>
                <td>{{ c.phone || '-' }}</td>
                <td class="text-sm max-w-xs truncate" :title="c.address">{{ c.address || '-' }}</td>
                <td>
                  <span :class="c.is_active ? 'badge badge-success' : 'badge badge-danger'">
                    {{ c.is_active ? 'Aktif' : 'Tidak Aktif' }}
                  </span>
                </td>
                <td>
                  <div class="flex gap-2">
                    <button @click="openModal(c)" class="text-blue-600 hover:text-blue-800" title="Edit">
                      <PencilIcon class="w-5 h-5" />
                    </button>
                    <button @click="confirmDelete(c)" class="text-red-600 hover:text-red-800" title="Hapus">
                      <TrashIcon class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="customers.last_page > 1" class="px-4 py-3 border-t flex flex-wrap items-center justify-between gap-2">
          <div class="text-sm text-gray-600">
            Menampilkan {{ (customers.current_page - 1) * customers.per_page + 1 }} -
            {{ Math.min(customers.current_page * customers.per_page, customers.total) }} dari {{ customers.total }} data
          </div>
          <div class="flex items-center gap-2">
            <select v-model="filters.per_page" @change="loadCustomers(1)" class="input py-1.5 text-sm w-20">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="text-sm text-gray-500">per halaman</span>
            <div class="flex gap-1 ml-2">
              <button type="button" @click="loadCustomers(customers.current_page - 1)" :disabled="customers.current_page <= 1"
                class="btn btn-sm btn-outline py-1 px-2">&laquo;</button>
              <button v-for="p in paginationPages" :key="p" type="button"
                @click="p !== '...' && loadCustomers(p)"
                :class="['btn btn-sm py-1 px-2', p === customers.current_page ? 'btn-primary' : 'btn-outline']"
                :disabled="p === '...'">{{ p }}</button>
              <button type="button" @click="loadCustomers(customers.current_page + 1)"
                :disabled="customers.current_page >= customers.last_page"
                class="btn btn-sm btn-outline py-1 px-2">&raquo;</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <CustomerFormModal v-if="showModal" :customer="editingCustomer"
      @close="showModal = false; editingCustomer = null"
      @saved="onSaved" />

    <div v-if="deleteTarget" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="deleteTarget = null">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-bold mb-2">Hapus Pelanggan?</h3>
        <p class="text-gray-600 mb-4">Pelanggan "{{ deleteTarget.name }}" akan dihapus. Lanjutkan?</p>
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
import CustomerFormModal from './CustomerFormModal.vue'

const toast = useToast()
const loading = ref(false)
const customers = ref({ data: [] })
const showModal = ref(false)
const editingCustomer = ref(null)
const deleteTarget = ref(null)
const deleting = ref(false)

const filters = ref({
  search: '',
  is_active: '',
  per_page: 25
})

let searchTimeout = null
const onSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadCustomers(1), 400)
}

const paginationPages = computed(() => {
  const cur = customers.value.current_page || 1
  const last = customers.value.last_page || 1
  if (last <= 7) return [...Array(last)].map((_, i) => i + 1)
  if (cur <= 4) return [1, 2, 3, 4, 5, '...', last]
  if (cur >= last - 3) return [1, '...', last - 4, last - 3, last - 2, last - 1, last]
  return [1, '...', cur - 1, cur, cur + 1, '...', last]
})

const loadCustomers = async (page) => {
  loading.value = true
  try {
    const params = { ...filters.value }
    params.page = page ?? customers.value?.current_page ?? 1
    const response = await api.get('/customers', { params })
    customers.value = response.data
  } catch (error) {
    toast.error('Gagal memuat data pelanggan')
  } finally {
    loading.value = false
  }
}

const openModal = (customer = null) => {
  editingCustomer.value = customer
  showModal.value = true
}

const onSaved = () => {
  loadCustomers()
}

const confirmDelete = (c) => {
  deleteTarget.value = c
}

const doDelete = async () => {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await api.delete(`/customers/${deleteTarget.value.id}`)
    toast.success('Pelanggan berhasil dihapus')
    deleteTarget.value = null
    loadCustomers()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus pelanggan')
  } finally {
    deleting.value = false
  }
}

onMounted(loadCustomers)
</script>
