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

/** Format stok tanpa trailing zeros (5 bukan 5.00) */
export const formatStock = (value) => {
  const n = Number(value);
  if (Number.isInteger(n)) return String(n);
  return String(parseFloat((n || 0).toFixed(2)));
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
