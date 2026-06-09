<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Data Kendaraan</h1>
      <button v-if="hasPermission('create_vehicles')" @click="openModal()" class="btn btn-primary flex items-center gap-2">
        <PlusIcon class="w-5 h-5" />
        Tambah Kendaraan
      </button>
    </div>

    <div class="bg-white rounded-lg shadow-sm">
      <div class="p-4 border-b flex justify-between items-center">
        <div class="relative w-64">
          <input v-model="search" type="text" placeholder="Cari kendaraan..." class="input pl-10" />
          <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>Plat Nomor</th>
              <th>Nama Kendaraan</th>
              <th>Jenis</th>
              <th v-if="hasPermission('edit_vehicles') || hasPermission('delete_vehicles')" class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="4" class="text-center py-4">Memuat data...</td>
            </tr>
            <tr v-else-if="vehicles.data.length === 0">
              <td colspan="4" class="text-center py-4 text-gray-500">Tidak ada data kendaraan</td>
            </tr>
            <tr v-else v-for="vehicle in vehicles.data" :key="vehicle.id">
              <td class="font-medium">{{ vehicle.license_plate }}</td>
              <td>{{ vehicle.name }}</td>
              <td>{{ vehicle.type || '-' }}</td>
              <td class="text-right space-x-2" v-if="hasPermission('edit_vehicles') || hasPermission('delete_vehicles')">
                <button v-if="hasPermission('edit_vehicles')" @click="openModal(vehicle)" class="text-blue-600 hover:text-blue-800">Edit</button>
                <button v-if="hasPermission('delete_vehicles')" @click="deleteVehicle(vehicle)" class="text-red-600 hover:text-red-800">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="p-4 border-t flex items-center justify-between" v-if="vehicles.last_page > 1">
        <span class="text-sm text-gray-600">
          Menampilkan {{ vehicles.from }} - {{ vehicles.to }} dari {{ vehicles.total }} data
        </span>
        <div class="flex gap-2">
          <button class="btn btn-secondary px-3 py-1" :disabled="vehicles.current_page === 1" @click="fetchVehicles(vehicles.current_page - 1)">
            Sebelumnya
          </button>
          <button class="btn btn-secondary px-3 py-1" :disabled="vehicles.current_page === vehicles.last_page" @click="fetchVehicles(vehicles.current_page + 1)">
            Selanjutnya
          </button>
        </div>
      </div>
    </div>

    <VehicleFormModal v-if="showModal" :vehicle="selectedVehicle" @close="closeModal" @saved="fetchVehicles(vehicles.current_page)" />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { PlusIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import debounce from 'lodash/debounce'
import api from '@/utils/axios'
import VehicleFormModal from './VehicleFormModal.vue'

const authStore = useAuthStore()
const hasPermission = (perm) => authStore.hasPermission(perm)

const toast = useToast()
const loading = ref(false)
const vehicles = ref({ data: [] })
const search = ref('')

const showModal = ref(false)
const selectedVehicle = ref(null)

const fetchVehicles = async (page = 1) => {
  loading.value = true
  try {
    const { data } = await api.get('/vehicles', {
      params: {
        page,
        search: search.value
      }
    })
    vehicles.value = data
  } catch (e) {
    toast.error('Gagal mengambil data kendaraan')
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => {
  fetchVehicles(1)
}, 500)

watch(search, () => {
  debouncedSearch()
})

onMounted(() => {
  fetchVehicles()
})

const openModal = (vehicle = null) => {
  selectedVehicle.value = vehicle
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedVehicle.value = null
}

const deleteVehicle = async (vehicle) => {
  if (confirm(`Hapus kendaraan ${vehicle.name} (${vehicle.license_plate})?`)) {
    try {
      await api.delete(`/vehicles/${vehicle.id}`)
      toast.success('Kendaraan berhasil dihapus')
      fetchVehicles(vehicles.value.current_page)
    } catch (e) {
      toast.error(e.response?.data?.message || 'Gagal menghapus kendaraan')
    }
  }
}
</script>
