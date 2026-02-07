import { defineStore } from "pinia";
import api from "@/utils/axios";
import { useToast } from "vue-toastification";

const toast = useToast();

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    token: localStorage.getItem("token") || null,
    permissions: [],
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,

    hasRole: (state) => (role) => {
      return state.user?.roles?.some((r) => r.name === role) || false;
    },

    hasPermission: (state) => (permission) => {
      if (!state.user) return false;

      // Super Admin has all permissions
      if (state.user.roles?.some((r) => r.name === "Super Admin")) {
        return true;
      }

      return state.permissions.includes(permission);
    },
  },

  actions: {
    async login(credentials) {
      try {
        const response = await api.post("/login", credentials);
        this.token = response.data.token;
        this.user = response.data.user;
        this.extractPermissions();

        localStorage.setItem("token", this.token);
        toast.success("Login berhasil!");
        return true;
      } catch (error) {
        const message = error.response?.data?.message || "Login gagal";
        toast.error(message);
        return false;
      }
    },

    async logout() {
      try {
        await api.post("/logout");
      } catch (error) {
        console.error("Logout error:", error);
      } finally {
        this.token = null;
        this.user = null;
        this.permissions = [];
        localStorage.removeItem("token");
        toast.success("Logout berhasil!");
      }
    },

    async checkAuth() {
      if (!this.token) return false;

      try {
        const response = await api.get("/me");
        this.user = response.data;
        this.extractPermissions();
        return true;
      } catch (error) {
        this.logout();
        return false;
      }
    },

    extractPermissions() {
      if (!this.user?.roles) return;

      const permissions = [];
      this.user.roles.forEach((role) => {
        role.permissions?.forEach((permission) => {
          if (!permissions.includes(permission.name)) {
            permissions.push(permission.name);
          }
        });
      });
      this.permissions = permissions;
    },
  },

  persist: {
    enabled: true,
    strategies: [
      {
        key: "auth",
        storage: localStorage,
        paths: ["token"],
      },
    ],
  },
});
