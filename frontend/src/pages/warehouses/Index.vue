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

    <!-- Modal Form Gudang -->
    <WarehouseFormModal
      v-if="showWarehouseModal"
      :warehouse="editingWarehouse"
      @close="closeWarehouseModal"
      @saved="handleWarehouseSaved"
    />

    <!-- Modal Form Rak -->
    <RackFormModal
      v-if="showRackModal"
      :rack="editingRack"
      :selected-warehouse="selectedWarehouse"
      @close="closeRackModal"
      @saved="handleRackSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { PlusIcon, PencilIcon } from '@heroicons/vue/24/outline'
import WarehouseFormModal from './WarehouseFormModal.vue'
import RackFormModal from './RackFormModal.vue'

const authStore = useAuthStore()
const toast = useToast()

const warehouses = ref([])
const racks = ref([])
const selectedWarehouse = ref(null)
const showWarehouseModal = ref(false)
const showRackModal = ref(false)
const editingWarehouse = ref(null)
const editingRack = ref(null)

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
  editingWarehouse.value = warehouse ?? null
  showWarehouseModal.value = true
}

const closeWarehouseModal = () => {
  showWarehouseModal.value = false
  editingWarehouse.value = null
}

const handleWarehouseSaved = () => {
  loadWarehouses()
}

const openRackModal = (rack = null) => {
  editingRack.value = rack ?? null
  showRackModal.value = true
}

const closeRackModal = () => {
  showRackModal.value = false
  editingRack.value = null
}

const handleRackSaved = () => {
  loadRacks()
}

onMounted(() => {
  loadWarehouses()
})
</script>
