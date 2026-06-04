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
            <tr v-for="unit in units" :key="unit.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-2.5 font-medium">{{ unit.code }}</td>
              <td class="px-4 py-2.5">{{ unit.name }}</td>
              <td class="px-4 py-2.5">{{ unit.description || '-' }}</td>
              <td class="px-4 py-2.5">
                <span :class="unit.is_active ? 'badge badge-success' : 'badge badge-danger'">
                  {{ unit.is_active ? 'Aktif' : 'Tidak Aktif' }}
                </span>
              </td>
              <td class="px-4 py-2.5">
                <div class="flex gap-2">
                  <button v-if="hasPermission('edit_units')" @click="openModal(unit)"
                    class="text-blue-600 hover:text-blue-800" title="Edit">
                    <PencilIcon class="w-5 h-5" />
                  </button>
                  <button v-if="hasPermission('delete_units')" @click="deleteUnit(unit)"
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

    <UnitFormModal v-if="showModal" :unit="selectedUnit" @close="showModal = false" @saved="handleSaved" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
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

const deleteUnit = async (unit) => {
  if (!confirm(`Yakin ingin menghapus satuan ${unit.name}?`)) return
  
  try {
    await api.delete(`/units/${unit.id}`)
    toast.success('Satuan berhasil dihapus')
    loadUnits()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus satuan')
  }
}

onMounted(loadUnits)
</script>
