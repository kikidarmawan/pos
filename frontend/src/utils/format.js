import { format } from "date-fns";
import { id } from "date-fns/locale";

export const formatCurrency = (value) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value || 0);
};

export const formatDate = (date, formatStr = "dd MMM yyyy") => {
  if (!date) return "-";
  return format(new Date(date), formatStr, { locale: id });
};

export const formatDateTime = (date) => {
  if (!date) return "-";
  return format(new Date(date), "dd MMM yyyy HH:mm", { locale: id });
};

export const formatNumber = (value, decimals = 0) => {
  return new Intl.NumberFormat("id-ID", {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(value || 0);
};

/** Format stok - number format tanpa 3 desimal (1 bukan 1.000, 2.5 tetap 2,5) */
export const formatStock = (value) => {
  const n = Number(value);
  return new Intl.NumberFormat("id-ID", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(n || 0);
};

export const formatPaymentMethod = (method) => {
  const labels = {
    cash: "Tunai",
    card: "Kartu",
    transfer: "Transfer",
    credit: "Utang",
    other: "Lainnya",
  };
  return labels[method] || method;
};
