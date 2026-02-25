<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <aside :class="[
      'fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300',
      sidebarOpen ? 'translate-x-0' : '-translate-x-full'
    ]">
      <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 bg-primary-600">
          <h1 class="text-2xl font-bold text-white">POS System</h1>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4">
          <template v-for="item in menuItems" :key="item.name">
            <!-- Parent with sub-menu -->
            <div v-if="item.children" v-show="item.children.some(c => !c.permission || hasPermission(c.permission))"
              class="border-b border-gray-100 last:border-0">
              <button type="button" @click="toggleMenu(item.name)"
                class="flex items-center justify-between w-full px-6 py-2.5 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors text-left">
                <div class="flex items-center">
                  <component :is="item.icon" class="w-4 h-4 mr-3 shrink-0" />
                  <span>{{ item.label }}</span>
                </div>
                <ChevronDownIcon :class="['w-4 h-4 transition-transform', expandedMenus.includes(item.name) ? '' : '-rotate-90']" />
              </button>
              <div v-show="expandedMenus.includes(item.name)" class="bg-gray-50/50">
                <router-link v-for="child in item.children" :key="child.name" :to="child.path"
                  v-show="!child.permission || hasPermission(child.permission)"
                  class="flex items-center pl-12 pr-6 py-2 text-xs text-gray-600 hover:bg-primary-50 hover:text-primary-600 transition-colors"
                  active-class="bg-primary-50 text-primary-600 border-r-4 border-primary-600 font-medium">
                  <component v-if="child.icon" :is="child.icon" class="w-4 h-4 mr-3 shrink-0" />
                  <span>{{ child.label }}</span>
                </router-link>
              </div>
            </div>
            <!-- Simple link (active class pakai route.path agar Dashboard hanya aktif di /) -->
            <router-link v-else :to="item.path"
              v-show="!item.permission || hasPermission(item.permission)"
              :class="[
                'flex items-center px-6 py-2.5 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors',
                isMenuActive(item) ? 'bg-primary-50 text-primary-600 border-r-4 border-primary-600' : ''
              ]">
              <component :is="item.icon" class="w-4 h-4 mr-3 shrink-0" />
              <span>{{ item.label }}</span>
            </router-link>
          </template>
        </nav>

        <!-- Versi Aplikasi -->
        <div class="px-6 py-3 border-t border-gray-100 text-center">
          <p class="text-xs text-gray-500">POS System v{{ appVersion }}</p>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div :class="['transition-[padding] duration-300', sidebarOpen ? 'lg:pl-64' : '']">
      <!-- Header -->
      <header class="bg-white shadow-sm sticky top-0 z-40">
        <!-- Notif langganan mau habis (7 hari lagi), bisa di-close -->
        <div
          v-if="subscriptionStore.isExpiringSoon && !subscriptionNotifDismissed"
          class="flex items-center justify-between gap-2 px-4 py-2 bg-amber-100 text-amber-800 text-sm border-b border-amber-200"
        >
          <div class="flex items-center justify-center gap-2 min-w-0 flex-1">
            <ExclamationTriangleIcon class="w-5 h-5 shrink-0" />
            <span>
              Masa langganan akan habis dalam
              <strong>{{ subscriptionStore.daysLeft }} hari</strong>.
              <button
                type="button"
                @click="subscriptionStore.openPayModal()"
                class="underline font-semibold hover:no-underline ml-1"
              >
                Perpanjang sekarang
              </button>
            </span>
          </div>
          <button
            type="button"
            @click="subscriptionNotifDismissed = true"
            class="p-1 rounded hover:bg-amber-200/80 text-amber-800 shrink-0"
            title="Tutup"
            aria-label="Tutup notifikasi"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
        <div class="flex items-center justify-between px-4 py-3 gap-4">
          <button
            type="button"
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 rounded-lg hover:bg-gray-100 text-gray-600 hover:text-gray-900 transition-colors"
            :title="sidebarOpen ? 'Tutup menu' : 'Buka menu'"
            aria-label="Toggle menu"
          >
            <Bars3Icon class="w-6 h-6" />
          </button>

          <!-- Tanggal & Waktu -->
          <div class="flex-1 flex justify-center">
            <div class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 px-4 py-2 rounded-lg border border-gray-200">
              <ClockIcon class="w-5 h-5 text-primary-600 shrink-0" />
              <span class="font-medium tabular-nums">{{ dateTime }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
              <p class="text-sm font-medium text-gray-900">{{ user?.name }}</p>
              <p class="text-xs text-gray-500">{{ user?.roles?.[0]?.name }}</p>
            </div>
            <router-link :to="{ name: 'Profile' }" class="p-2 rounded-lg hover:bg-gray-100" title="Profil">
              <UserCircleIcon class="w-6 h-6 text-gray-600" />
            </router-link>
            <button @click="handleLogout" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
              <ArrowRightOnRectangleIcon class="w-5 h-5" />
              <span class="hidden sm:inline">Logout</span>
            </button>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="p-6">
        <router-view />
      </main>
    </div>

    <!-- Overlay saat sidebar terbuka (mobile) -->
    <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden" aria-hidden="true"></div>

    <!-- Modal langganan habis (harus bayar untuk lanjut) -->
    <SubscriptionExpiredModal :show="subscriptionStore.showExpiredModal" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useSubscriptionStore } from '@/stores/subscription'
import SubscriptionExpiredModal from '@/components/SubscriptionExpiredModal.vue'
import {
  HomeIcon,
  UserGroupIcon,
  ShieldCheckIcon,
  TagIcon,
  ScaleIcon,
  BuildingStorefrontIcon,
  TruckIcon,
  CubeIcon,
  ShoppingCartIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  Bars3Icon,
  UserCircleIcon,
  ArrowRightOnRectangleIcon,
  ChevronDownIcon,
  IdentificationIcon,
  KeyIcon,
  DocumentChartBarIcon,
  ArrowPathIcon,
  AdjustmentsHorizontalIcon,
  ArrowTrendingUpIcon,
  ArrowDownIcon,
  ArrowUpIcon,
  ListBulletIcon,
  BanknotesIcon,
  Cog6ToothIcon,
  PrinterIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const subscriptionStore = useSubscriptionStore()

/** Versi aplikasi (di-inject dari package.json saat build) */
const appVersion = typeof __APP_VERSION__ !== 'undefined' ? __APP_VERSION__ : '1.0.0'

const sidebarOpen = ref(true)
const subscriptionNotifDismissed = ref(false)
const user = computed(() => authStore.user)
const dateTime = ref('')

function formatNavbarDateTime() {
  const now = new Date()
  const dateStr = now.toLocaleDateString('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
  const timeStr = now.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  })
  return `${dateStr} | ${timeStr}`
}

let dateTimeInterval = null
onMounted(() => {
  dateTime.value = formatNavbarDateTime()
  dateTimeInterval = setInterval(() => {
    dateTime.value = formatNavbarDateTime()
  }, 1000)
  subscriptionStore.fetchCurrent()
})
onUnmounted(() => {
  if (dateTimeInterval) clearInterval(dateTimeInterval)
})

const menuItems = [
  { name: 'dashboard', label: 'Dashboard', path: '/', icon: HomeIcon },
  {
    name: 'kontak',
    label: 'Kontak',
    icon: IdentificationIcon,
    children: [
      { name: 'customers', label: 'Pelanggan', path: '/customers', icon: UserCircleIcon },
      { name: 'suppliers', label: 'Supplier', path: '/suppliers', icon: TruckIcon, permission: 'view_suppliers' },
    ],
  },
  {
    name: 'manajemen-pengguna',
    label: 'Manajemen Pengguna',
    icon: UserGroupIcon,
    children: [
      { name: 'users', label: 'Data Pengguna', path: '/users', icon: UserGroupIcon, permission: 'view_users' },
      { name: 'permissions', label: 'Hak Akses', path: '/permissions', icon: KeyIcon, permission: 'view_roles' },
      { name: 'roles', label: 'Peran', path: '/roles', icon: ShieldCheckIcon, permission: 'view_roles' },
    ],
  },
  { name: 'pos', label: 'Point of Sale', path: '/pos', icon: CurrencyDollarIcon, permission: 'create_sales' },
  {
    name: 'penjualan',
    label: 'Penjualan',
    icon: ShoppingCartIcon,
    permission: 'view_sales',
    children: [
      { name: 'sales', label: 'Daftar Penjualan', path: '/sales', icon: ListBulletIcon, permission: 'view_sales' },
      { name: 'sale-returns', label: 'Retur Penjualan', path: '/sale-returns', icon: ArrowPathIcon, permission: 'view_sales' },
    ],
  },
  {
    name: 'pembelian',
    label: 'Pembelian',
    icon: TruckIcon,
    permission: 'view_purchases',
    children: [
      { name: 'purchases', label: 'Daftar Pembelian', path: '/purchases', icon: ListBulletIcon, permission: 'view_purchases' },
      { name: 'purchase-returns', label: 'Retur Pembelian', path: '/purchase-returns', icon: ArrowPathIcon, permission: 'view_purchases' },
    ],
  },
  {
    name: 'kelola-produk',
    label: 'Kelola Produk',
    icon: CubeIcon,
    children: [
      { name: 'products', label: 'Data Produk', path: '/products', icon: CubeIcon, permission: 'view_products' },
      { name: 'categories', label: 'Kategori Produk', path: '/categories', icon: TagIcon, permission: 'view_categories' },
      { name: 'units', label: 'Satuan Produk', path: '/units', icon: ScaleIcon, permission: 'view_units' },
    ],
  },
  { name: 'stocks', label: 'Penyesuaian Stok', path: '/stocks', icon: ChartBarIcon, permission: 'view_stocks' },
  { name: 'warehouses', label: 'Gudang', path: '/warehouses', icon: BuildingStorefrontIcon, permission: 'view_warehouses' },
  {
    name: 'pengaturan',
    label: 'Pengaturan',
    icon: Cog6ToothIcon,
    children: [
      { name: 'store', label: 'Identitas Toko', path: '/settings/store', icon: BuildingStorefrontIcon },
      { name: 'printer', label: 'Pengaturan Printer', path: '/settings/printer', icon: PrinterIcon },
    ],
  },
  {
    name: 'laporan',
    label: 'Laporan',
    icon: ChartBarIcon,
    permission: 'view_reports',
    children: [
      { name: 'profit-report', label: 'Laporan Laba/Rugi', path: '/reports/profit', icon: DocumentChartBarIcon, permission: 'view_reports' },
      { name: 'purchase-sale-report', label: 'Pembelian & Penjualan', path: '/reports/purchase-sale', icon: ArrowPathIcon, permission: 'view_reports' },
      { name: 'supplier-customer-report', label: 'Laporan Supplier & Pelanggan', path: '/reports/supplier-customer', icon: UserGroupIcon, permission: 'view_reports' },
      { name: 'stock-report', label: 'Laporan Stok', path: '/reports/stock', icon: ChartBarIcon, permission: 'view_reports' },
      { name: 'stock-adjustment-report', label: 'Laporan Penyesuaian Stok', path: '/reports/stock-adjustment', icon: AdjustmentsHorizontalIcon, permission: 'view_reports' },
      { name: 'trending-products-report', label: 'Produk Terlaris', path: '/reports/trending-products', icon: ArrowTrendingUpIcon, permission: 'view_reports' },
      { name: 'items-report', label: 'Laporan Item', path: '/reports/items', icon: ListBulletIcon, permission: 'view_reports' },
      { name: 'product-purchase-report', label: 'Laporan Pembelian Produk', path: '/reports/purchases', icon: ArrowDownIcon, permission: 'view_reports' },
      { name: 'product-sell-report', label: 'Laporan Penjualan Produk', path: '/reports/sales', icon: ArrowUpIcon, permission: 'view_reports' },
      { name: 'purchase-payment-report', label: 'Laporan Pembayaran Pembelian', path: '/reports/purchase-payment', icon: BanknotesIcon, permission: 'view_reports' },
      { name: 'sell-payment-report', label: 'Laporan Pembayaran Penjualan', path: '/reports/sell-payment', icon: BanknotesIcon, permission: 'view_reports' },
      { name: 'daily-cashier-summary', label: 'Daily Cashier POS Summary', path: '/reports/daily-cashier-summary', icon: BanknotesIcon, permission: 'view_reports' },
    ],
  },
]

const expandedMenus = ref([])

/** Dashboard (path '/') hanya aktif di halaman root; menu lain aktif jika path diawali path menu */
const isMenuActive = (item) => {
  if (item.path === '/') return route.path === '/'
  return route.path.startsWith(item.path)
}

const toggleMenu = (name) => {
  const idx = expandedMenus.value.indexOf(name)
  if (idx >= 0) {
    expandedMenus.value = expandedMenus.value.filter((m) => m !== name)
  } else {
    expandedMenus.value = [...expandedMenus.value, name]
  }
}

// Auto-expand parent when on child route
watch(() => route.path, (path) => {
  const parent = menuItems.find((m) => m.children?.some((c) => path.startsWith(c.path)))
  if (parent && !expandedMenus.value.includes(parent.name)) {
    expandedMenus.value = [...expandedMenus.value, parent.name]
  }
}, { immediate: true })

const hasPermission = (permission) => {
  return authStore.hasPermission(permission)
}

const handleLogout = async () => {
  await authStore.logout()
  router.push({ name: 'Login' })
}
</script>
