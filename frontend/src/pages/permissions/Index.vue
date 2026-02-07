<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Hak Akses</h1>
    <p class="text-gray-600 mb-6">Daftar permission yang tersedia di sistem. Permission diatur per role di menu Peran.</p>

    <div v-if="loading" class="card p-8 text-center">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      <p class="mt-2 text-gray-600">Memuat data...</p>
    </div>

    <div v-else class="grid gap-4">
      <div v-for="(items, group) in permissions" :key="group" class="card">
        <h2 class="text-lg font-semibold text-gray-900 mb-3 capitalize">{{ group }}</h2>
        <div class="flex flex-wrap gap-2">
          <span v-for="p in items" :key="p.id" class="badge badge-info">
            {{ p.name }}
          </span>
        </div>
      </div>
      <div v-if="Object.keys(permissions).length === 0" class="card p-8 text-center text-gray-500">
        Belum ada permission terdaftar.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'

const toast = useToast()
const loading = ref(false)
const permissions = ref({})

const loadPermissions = async () => {
  loading.value = true
  try {
    const res = await api.get('/permissions')
    permissions.value = res.data
  } catch (e) {
    toast.error('Gagal memuat hak akses')
  } finally {
    loading.value = false
  }
}

onMounted(loadPermissions)
</script>
