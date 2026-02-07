<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Manajemen Pengguna</h1>

    <div class="card">
      <p class="text-gray-600 mb-4">
        Halaman untuk mengelola user dengan role & permission. Fitur lengkap dapat dikembangkan sesuai kebutuhan.
      </p>
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-800">
          <strong>Info:</strong> User management sudah tersupport di backend API.
          Route: GET/POST/PUT/DELETE /api/users dengan role & permission assignment.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const hasPermission = (permission) => authStore.hasPermission(permission)
</script>
