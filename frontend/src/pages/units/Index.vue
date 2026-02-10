<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Satuan Produk</h1>
      <button v-if="hasPermission('create_units')" @click="openModal()" class="btn btn-primary">
        <PlusIcon class="w-5 h-5 mr-2" />
        Tambah Satuan
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
            <tr v-for="unit in units" :key="unit.id">
              <td class="font-medium">{{ unit.code }}</td>
              <td>{{ unit.name }}</td>
              <td>{{ unit.description || '-' }}</td>
              <td>
                <span :class="unit.is_active ? 'badge badge-success' : 'badge badge-danger'">
                  {{ unit.is_active ? 'Aktif' : 'Tidak Aktif' }}
                </span>
              </td>
              <td>
                <button v-if="hasPermission('edit_units')" @click="openModal(unit)"
                  class="text-blue-600 hover:text-blue-800">
                  <PencilIcon class="w-5 h-5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <UnitFormModal v-if="showModal" :unit="selectedUnit" @close="showModal = false" @saved="handleSaved" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { PlusIcon, PencilIcon } from '@heroicons/vue/24/outline'
import UnitFormModal from './UnitFormModal.vue'

const authStore = useAuthStore()
const toast = useToast()
const units = ref([])
const showModal = ref(false)
const selectedUnit = ref(null)

const hasPermission = (permission) => authStore.hasPermission(permission)

const loadUnits = async () => {
  try {
    const response = await api.get('/units', { params: { per_page: 100 } })
    units.value = response.data.data
  } catch (error) {
    toast.error('Gagal memuat satuan')
  }
}

const openModal = (unit = null) => {
  selectedUnit.value = unit
  showModal.value = true
}

const handleSaved = () => {
  loadUnits()
}

onMounted(loadUnits)
</script>
