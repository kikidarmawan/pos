import { defineStore } from "pinia";

export const useCartStore = defineStore("cart", {
  state: () => ({
    items: [],
    customer: {
      id: null,
      name: "",
      phone: "",
      address: "",
    },
    discount: 0,
    tax: 0,
  }),

  getters: {
    itemCount: (state) => state.items.length,

    subtotal: (state) => {
      return state.items.reduce((total, item) => {
        // Item bonus tidak dihitung ke subtotal
        if (item.isBonus) return total;
        const base = item.price * item.quantity;
        const discountPerQty = item.discount || 0;
        const totalDiscount = discountPerQty * item.quantity;
        return total + (base - totalDiscount);
      }, 0);
    },

    taxAmount() {
      return (this.subtotal - this.discount) * (this.tax / 100);
    },

    total() {
      return this.subtotal - this.discount + this.taxAmount;
    },
  },

  actions: {
    addItem(product, unit, productUnit) {
      const existingItem = this.items.find(
        (item) =>
          item.product.id === product.id &&
          item.unit.id === unit.id &&
          !item.isBonus,
      );

      if (existingItem) {
        existingItem.quantity++;
        const base = existingItem.quantity * existingItem.price;
        const totalDiscount =
          (existingItem.discount || 0) * existingItem.quantity;
        existingItem.subtotal = base - totalDiscount;
      } else {
        const price = productUnit?.selling_price || product.base_price;
        this.items.push({
          product,
          unit,
          productUnit,
          quantity: 1,
          price,
          discount: 0,
          subtotal: price,
          isBonus: false,
        });
      }
    },

    addBonusItem(product, unit, productUnit) {
      const existingItem = this.items.find(
        (item) =>
          item.product.id === product.id &&
          item.unit.id === unit.id &&
          item.isBonus,
      );

      if (existingItem) {
        existingItem.quantity++;
        // Bonus tetap subtotal = 0
      } else {
        this.items.push({
          product,
          unit,
          productUnit,
          quantity: 1,
          price: 0,
          discount: 0,
          subtotal: 0,
          isBonus: true,
        });
      }
    },

    removeItem(index) {
      this.items.splice(index, 1);
    },

    updateQuantity(index, quantity) {
      if (quantity <= 0) {
        this.removeItem(index);
      } else {
        const item = this.items[index];
        item.quantity = quantity;
        if (item.isBonus) {
          item.subtotal = 0;
        } else {
          const base = item.price * quantity;
          const totalDiscount = (item.discount || 0) * quantity;
          item.subtotal = Math.max(0, base - totalDiscount);
        }
      }
    },

    updatePrice(index, price) {
      const item = this.items[index];
      if (item.isBonus) return; // Bonus tidak bisa ubah harga
      item.price = price;
      const base = price * item.quantity;
      const totalDiscount = (item.discount || 0) * item.quantity;
      item.subtotal = Math.max(0, base - totalDiscount);
    },

    updateItemDiscount(index, discountPerQty) {
      const item = this.items[index];
      if (item.isBonus) return; // Bonus tidak ada diskon
      const discountVal = Math.max(0, parseFloat(discountPerQty) || 0);
      const base = item.price * item.quantity;
      const totalDiscount = discountVal * item.quantity;
      const subtotal = Math.max(0, base - totalDiscount);
      this.items.splice(index, 1, {
        ...item,
        discount: discountVal,
        subtotal,
      });
    },

    changeItemUnit(index, unit, productUnit) {
      const item = this.items[index];
      item.unit = unit;
      item.productUnit = productUnit;
      if (item.isBonus) {
        item.price = 0;
        item.subtotal = 0;
      } else {
        item.price = productUnit?.selling_price || item.product.base_price;
        const base = item.price * item.quantity;
        const totalDiscount = (item.discount || 0) * item.quantity;
        item.subtotal = Math.max(0, base - totalDiscount);
      }
    },

    setDiscount(discount) {
      this.discount = discount;
    },

    setTax(tax) {
      this.tax = tax;
    },

    setCustomer(customer) {
      this.customer = customer;
    },

    loadFromHold(data) {
      this.items = data.items || [];
      this.customer = data.customer || {
        id: null,
        name: "",
        phone: "",
        address: "",
      };
      this.discount = data.discount ?? 0;
      this.tax = data.tax ?? 0;
    },

    clear() {
      this.items = [];
      this.customer = { id: null, name: "", phone: "", address: "" };
      this.discount = 0;
      this.tax = 0;
    },
  },

  persist: {
    enabled: true,
    strategies: [
      {
        key: "cart",
        storage: localStorage,
      },
    ],
  },
});
