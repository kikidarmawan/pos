<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Data Supplier</h1>
      <button v-if="hasPermission('create_suppliers')" @click="openModal()" class="btn btn-primary">
        <PlusIcon class="w-5 h-5 mr-2" />
        Tambah Supplier
      </button>
    </div>

    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama</th>
              <th>Kontak</th>
              <th>Alamat</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="supplier in suppliers" :key="supplier.id">
              <td class="font-medium">{{ supplier.code }}</td>
              <td>
                <div class="font-medium">{{ supplier.name }}</div>
                <div class="text-xs text-gray-500">{{ supplier.contact_person || '-' }}</div>
              </td>
              <td>
                <div class="text-sm">
                  <div>{{ supplier.phone || '-' }}</div>
                  <div class="text-gray-500">{{ supplier.email || '-' }}</div>
                </div>
              </td>
              <td class="text-sm">{{ supplier.address || '-' }}</td>
              <td>
                <span :class="supplier.is_active ? 'badge badge-success' : 'badge badge-danger'">
                  {{ supplier.is_active ? 'Aktif' : 'Tidak Aktif' }}
                </span>
              </td>
              <td>
                <button v-if="hasPermission('edit_suppliers')" @click="openModal(supplier)"
                  class="text-blue-600 hover:text-blue-800">
                  <PencilIcon class="w-5 h-5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
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
const suppliers = ref([])

const hasPermission = (permission) => authStore.hasPermission(permission)

const loadSuppliers = async () => {
  try {
    const response = await api.get('/suppliers', { params: { per_page: 100 } })
    suppliers.value = response.data.data
  } catch (error) {
    toast.error('Gagal memuat supplier')
  }
}

const openModal = (supplier = null) => {
  const name = supplier ? prompt('Nama Supplier:', supplier.name) : prompt('Nama Supplier:')
  if (!name) return

  const code = supplier ? prompt('Kode Supplier:', supplier.code) : prompt('Kode Supplier:')
  if (!code) return

  const phone = supplier ? prompt('Telepon:', supplier.phone) : prompt('Telepon:')
  const email = supplier ? prompt('Email:', supplier.email) : prompt('Email:')

  saveSupplier({ name, code, phone, email }, supplier)
}

const saveSupplier = async (data, supplier = null) => {
  try {
    if (supplier) {
      await api.put(`/suppliers/${supplier.id}`, data)
      toast.success('Supplier berhasil diperbarui')
    } else {
      await api.post('/suppliers', data)
      toast.success('Supplier berhasil ditambahkan')
    }
    loadSuppliers()
  } catch (error) {
    toast.error('Gagal menyimpan supplier')
  }
}

onMounted(loadSuppliers)
</script>
