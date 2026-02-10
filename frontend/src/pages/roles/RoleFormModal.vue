<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">{{ role ? 'Edit Peran' : 'Tambah Peran Baru' }}</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="label">Nama Peran *</label>
          <input v-model="form.name" type="text" required class="input" placeholder="Contoh: Kasir, Gudang" />
        </div>
        <div>
          <label class="label">Hak Akses (Permission)</label>
          <div class="space-y-3 max-h-64 overflow-y-auto border rounded p-3 bg-gray-50">
            <div v-for="(items, resource) in permissionsByResource" :key="resource" class="space-y-2">
              <p class="text-sm font-medium text-gray-700 capitalize">{{ resource }}</p>
              <div class="flex flex-wrap gap-2 pl-2">
                <label v-for="p in items" :key="p.id" class="flex items-center gap-1.5 cursor-pointer text-sm">
                  <input v-model="form.permissions" type="checkbox" :value="p.name" class="rounded border-gray-300 text-primary-600" />
                  <span>{{ p.name.replace('_', ' ') }}</span>
                </label>
              </div>
            </div>
            <p v-if="!allPermissions.length" class="text-sm text-gray-500">Belum ada permission</p>
          </div>
        </div>
        <div class="flex gap-3 pt-4">
          <button type="button" @click="$emit('close')" class="flex-1 btn btn-secondary">Batal</button>
          <button type="submit" class="flex-1 btn btn-primary" :disabled="submitting">
            {{ submitting ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import api from '@/utils/axios'

const props = defineProps({
  role: { type: Object, default: null }
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const submitting = ref(false)
const permissionsRaw = ref({})

const form = reactive({
  name: '',
  permissions: []
})

const allPermissions = computed(() => {
  const obj = permissionsRaw.value
  if (!obj || typeof obj !== 'object') return []
  return Object.values(obj).flat()
})

const permissionsByResource = computed(() => {
  const list = allPermissions.value
  const byResource = {}
  list.forEach((p) => {
    const parts = (p.name || '').split('_')
    const resource = parts.length > 1 ? parts.slice(1).join('_') : 'lainnya'
    if (!byResource[resource]) byResource[resource] = []
    byResource[resource].push(p)
  })
  return byResource
})

onMounted(loadPermissions)
async function loadPermissions() {
  try {
    const res = await api.get('/permissions')
    permissionsRaw.value = res.data || {}
  } catch (e) {
    toast.error('Gagal memuat hak akses')
  }
}

watch(() => props.role, (r) => {
  if (r) {
    form.name = r.name || ''
    form.permissions = (r.permissions || []).map((p) => p.name)
  } else {
    form.name = ''
    form.permissions = []
  }
}, { immediate: true })

const handleSubmit = async () => {
  submitting.value = true
  try {
    const payload = {
      name: form.name.trim(),
      permissions: form.permissions
    }
    if (props.role) {
      await api.put(`/roles/${props.role.id}`, payload)
      toast.success('Peran berhasil diperbarui')
    } else {
      await api.post('/roles', payload)
      toast.success('Peran berhasil ditambahkan')
    }
    emit('saved')
    emit('close')
  } catch (e) {
    const msg = e.response?.data?.message || e.message || 'Gagal menyimpan'
    toast.error(msg)
  } finally {
    submitting.value = false
  }
}
</script>
