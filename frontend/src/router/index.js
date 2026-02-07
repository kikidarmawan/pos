import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const routes = [
  {
    path: "/login",
    name: "Login",
    component: () => import("@/pages/auth/Login.vue"),
    meta: { requiresGuest: true },
  },
  {
    path: "/",
    component: () => import("@/layouts/DashboardLayout.vue"),
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "Dashboard",
        component: () => import("@/pages/Dashboard.vue"),
      },
      // User Management
      {
        path: "users",
        name: "Users",
        component: () => import("@/pages/users/Index.vue"),
        meta: { permission: "view_users" },
      },
      {
        path: "roles",
        name: "Roles",
        component: () => import("@/pages/roles/Index.vue"),
        meta: { permission: "view_roles" },
      },
      {
        path: "permissions",
        name: "Permissions",
        component: () => import("@/pages/permissions/Index.vue"),
        meta: { permission: "view_roles" },
      },
      // Master Data
      {
        path: "categories",
        name: "Categories",
        component: () => import("@/pages/categories/Index.vue"),
        meta: { permission: "view_categories" },
      },
      {
        path: "units",
        name: "Units",
        component: () => import("@/pages/units/Index.vue"),
        meta: { permission: "view_units" },
      },
      {
        path: "warehouses",
        name: "Warehouses",
        component: () => import("@/pages/warehouses/Index.vue"),
        meta: { permission: "view_warehouses" },
      },
      {
        path: "suppliers",
        name: "Suppliers",
        component: () => import("@/pages/suppliers/Index.vue"),
        meta: { permission: "view_suppliers" },
      },
      {
        path: "customers",
        name: "Customers",
        component: () => import("@/pages/customers/Index.vue"),
      },
      // Products
      {
        path: "products",
        name: "Products",
        component: () => import("@/pages/products/Index.vue"),
        meta: { permission: "view_products" },
      },
      // Purchases
      {
        path: "purchases",
        name: "Purchases",
        component: () => import("@/pages/purchases/Index.vue"),
        meta: { permission: "view_purchases" },
      },
      {
        path: "purchases/create",
        name: "PurchaseCreate",
        component: () => import("@/pages/purchases/Create.vue"),
        meta: { permission: "create_purchases" },
      },
      // Sales/POS
      {
        path: "pos",
        name: "POS",
        component: () => import("@/pages/pos/Index.vue"),
        meta: { permission: "create_sales" },
      },
      {
        path: "sales",
        name: "Sales",
        component: () => import("@/pages/sales/Index.vue"),
        meta: { permission: "view_sales" },
      },
      // Stock
      {
        path: "stocks",
        name: "Stocks",
        component: () => import("@/pages/stocks/Index.vue"),
        meta: { permission: "view_stocks" },
      },
      // Reports
      {
        path: "reports/sales",
        name: "SalesReport",
        component: () => import("@/pages/reports/Sales.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/purchases",
        name: "PurchasesReport",
        component: () => import("@/pages/reports/Purchases.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/stock",
        name: "StockReport",
        component: () => import("@/pages/reports/Stock.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/profit",
        name: "ProfitReport",
        component: () => import("@/pages/reports/Profit.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/purchase-sale",
        name: "PurchaseSaleReport",
        component: () => import("@/pages/reports/PurchaseSale.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/tax",
        name: "TaxReport",
        component: () => import("@/pages/reports/Tax.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/supplier-customer",
        name: "SupplierCustomerReport",
        component: () => import("@/pages/reports/SupplierCustomer.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/customer-groups",
        name: "CustomerGroupsReport",
        component: () => import("@/pages/reports/CustomerGroups.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/stock-adjustment",
        name: "StockAdjustmentReport",
        component: () => import("@/pages/reports/StockAdjustment.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/trending-products",
        name: "TrendingProductsReport",
        component: () => import("@/pages/reports/TrendingProducts.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/items",
        name: "ItemsReport",
        component: () => import("@/pages/reports/Items.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/purchase-payment",
        name: "PurchasePaymentReport",
        component: () => import("@/pages/reports/PurchasePayment.vue"),
        meta: { permission: "view_reports" },
      },
      {
        path: "reports/sell-payment",
        name: "SellPaymentReport",
        component: () => import("@/pages/reports/SellPayment.vue"),
        meta: { permission: "view_reports" },
      },
      // Settings
      {
        path: "settings/store",
        name: "Store",
        component: () => import("@/pages/settings/Store.vue"),
      },
      // Profile
      {
        path: "profile",
        name: "Profile",
        component: () => import("@/pages/Profile.vue"),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: "Login" });
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next({ name: "Dashboard" });
  } else if (
    to.meta.permission &&
    !authStore.hasPermission(to.meta.permission)
  ) {
    next({ name: "Dashboard" });
  } else {
    next();
  }
});

export default router;
