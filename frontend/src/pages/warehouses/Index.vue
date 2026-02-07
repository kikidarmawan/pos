<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Gudang & Posisi Rak Penyimpanan</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Warehouses -->
      <div class="card">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Gudang</h2>
          <button v-if="hasPermission('create_warehouses')" @click="openWarehouseModal()"
            class="btn btn-primary btn-sm">
            <PlusIcon class="w-4 h-4 mr-1" />
            Tambah
          </button>
        </div>

        <div class="space-y-2">
          <div v-for="warehouse in warehouses" :key="warehouse.id"
            class="p-4 border rounded-lg hover:border-primary-600 cursor-pointer transition-colors"
            @click="selectWarehouse(warehouse)"
            :class="{ 'border-primary-600 bg-primary-50': selectedWarehouse?.id === warehouse.id }">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="font-medium">{{ warehouse.name }}</h3>
                <p class="text-sm text-gray-500">{{ warehouse.code }}</p>
                <p class="text-xs text-gray-400">{{ warehouse.address }}</p>
              </div>
              <div class="flex gap-2">
                <button v-if="hasPermission('edit_warehouses')" @click.stop="openWarehouseModal(warehouse)"
                  class="text-blue-600 hover:text-blue-800">
                  <PencilIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Racks -->
      <div class="card">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">
            Rak Penyimpanan {{ selectedWarehouse ? `- ${selectedWarehouse.name}` : '' }}
          </h2>
          <button v-if="selectedWarehouse && hasPermission('create_warehouses')" @click="openRackModal()"
            class="btn btn-primary btn-sm">
            <PlusIcon class="w-4 h-4 mr-1" />
            Tambah Rak
          </button>
        </div>

        <div v-if="selectedWarehouse" class="space-y-2">
          <div v-for="rack in racks" :key="rack.id" class="p-4 border rounded-lg">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="font-medium">{{ rack.name }}</h3>
                <p class="text-sm text-gray-500">Kode: {{ rack.code }}</p>
                <p class="text-xs text-gray-400">{{ rack.description || '-' }}</p>
              </div>
              <button v-if="hasPermission('edit_warehouses')" @click="openRackModal(rack)"
                class="text-blue-600 hover:text-blue-800">
                <PencilIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
          <div v-if="!racks.length" class="text-center text-gray-500 py-8">
            Belum ada rak
          </div>
        </div>
        <div v-else class="text-center text-gray-500 py-8">
          Pilih gudang untuk melihat rak
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { PlusIcon, PencilIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toast = useToast()

const warehouses = ref([])
const racks = ref([])
const selectedWarehouse = ref(null)

const hasPermission = (permission) => authStore.hasPermission(permission)

const loadWarehouses = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 100 } })
    warehouses.value = response.data.data
    if (warehouses.value.length > 0) {
      selectWarehouse(warehouses.value[0])
    }
  } catch (error) {
    toast.error('Gagal memuat gudang')
  }
}

const selectWarehouse = (warehouse) => {
  selectedWarehouse.value = warehouse
  loadRacks()
}

const loadRacks = async () => {
  if (!selectedWarehouse.value) return

  try {
    const response = await api.get('/racks', {
      params: { warehouse_id: selectedWarehouse.value.id, per_page: 100 }
    })
    racks.value = response.data.data
  } catch (error) {
    toast.error('Gagal memuat rak')
  }
}

const openWarehouseModal = (warehouse = null) => {
  const name = warehouse ? prompt('Nama Gudang:', warehouse.name) : prompt('Nama Gudang:')
  if (!name) return

  const code = warehouse ? prompt('Kode Gudang:', warehouse.code) : prompt('Kode Gudang:')
  if (!code) return

  const address = warehouse ? prompt('Alamat:', warehouse.address) : prompt('Alamat:')

  saveWarehouse({ name, code, address }, warehouse)
}

const saveWarehouse = async (data, warehouse = null) => {
  try {
    if (warehouse) {
      await api.put(`/warehouses/${warehouse.id}`, data)
      toast.success('Gudang berhasil diperbarui')
    } else {
      await api.post('/warehouses', data)
      toast.success('Gudang berhasil ditambahkan')
    }
    loadWarehouses()
  } catch (error) {
    toast.error('Gagal menyimpan gudang')
  }
}

const openRackModal = (rack = null) => {
  const name = rack ? prompt('Nama Rak:', rack.name) : prompt('Nama Rak:')
  if (!name) return

  const code = rack ? prompt('Kode Rak:', rack.code) : prompt('Kode Rak:')
  if (!code) return

  saveRack({ name, code, warehouse_id: selectedWarehouse.value.id }, rack)
}

const saveRack = async (data, rack = null) => {
  try {
    if (rack) {
      await api.put(`/racks/${rack.id}`, data)
      toast.success('Rak berhasil diperbarui')
    } else {
      await api.post('/racks', data)
      toast.success('Rak berhasil ditambahkan')
    }
    loadRacks()
  } catch (error) {
    toast.error('Gagal menyimpan rak')
  }
}

onMounted(() => {
  loadWarehouses()
})
</script>
