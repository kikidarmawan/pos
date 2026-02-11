import { formatCurrency, formatDateTime, formatPaymentMethod } from './format'
import api from './axios'

const STORAGE_KEY = 'pos_thermal_printer_device'

/**
 * Cetak struk via backend (ESC/POS) - printer harus terhubung ke server
 * @param {Object} sale - Objek transaksi (harus punya id)
 * @returns {Promise<{success: boolean}>}
 */
export async function printReceiptViaBackend(sale) {
  const id = sale?.id
  if (!id) throw new Error('ID transaksi tidak valid')
  const res = await api.post(`/sales/${id}/print-receipt`)
  return { success: true, message: res.data?.message }
}

/**
 * Print ke printer thermal (XPrinter 58IIZ, XP-58, dll) via Web Serial + ESC/POS
 * Tanpa dialog pilih port - langsung cetak ke printer yang sudah disambungkan
 */

async function getPort(options = {}) {
  const { detectPrinter = false } = options
  if (!('serial' in navigator)) {
    throw new Error('Web Serial tidak didukung. Gunakan Chrome atau Edge.')
  }
  let saved
  try {
    saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null')
  } catch (_) {}
  if (saved?.vendorId && saved?.productId) {
    const ports = await navigator.serial.getPorts()
    for (const port of ports) {
      try {
        const info = port.getInfo()
        if (info.usbVendorId === saved.vendorId && info.usbProductId === saved.productId) {
          return port
        }
      } catch (_) {}
    }
  }
  if (detectPrinter) {
    const confirmed = window.confirm(
      'Deteksi printer thermal (USB atau Bluetooth). Pilih device printer di jendela yang akan muncul.'
    )
    if (!confirmed) return null
    try {
      const port = await navigator.serial.requestPort()
      const info = port.getInfo()
      if (info.usbVendorId && info.usbProductId) {
        try {
          localStorage.setItem(STORAGE_KEY, JSON.stringify({
            vendorId: info.usbVendorId,
            productId: info.usbProductId,
          }))
        } catch (_) {}
      }
      return port
    } catch (e) {
      if (e.name === 'NotFoundError' || e.message?.includes('cancel')) return null
      throw e
    }
  }
  throw new Error('Printer belum terhubung. Klik tombol Printer di halaman POS atau pilih "Deteksi printer" saat cetak dari detail.')
}

/**
 * Connect ke printer thermal
 * @param {Object} options - { detectPrinter: true } untuk deteksi via USB/Bluetooth saat belum tersambung
 */
export async function connect(options = {}) {
  const port = await getPort(options)
  if (!port) return null
  let writer = null

  return {
    async open() {
      if (!port.readable) {
        await port.open({ baudRate: 9600 })
      }
    },
    async send(chunk) {
      await this.open()
      if (!writer) writer = port.writable.getWriter()
      await writer.write(chunk)
    },
    async close() {
      if (writer) {
        writer.releaseLock()
        writer = null
      }
    },
  }
}

/**
 * Daftar port yang sudah pernah dipilih user (USB/Bluetooth) - untuk tampil di modal custom
 * @returns {Promise<Array<{ port: SerialPort, label: string }>>}
 */
export async function getAvailablePrinterPorts() {
  if (!('serial' in navigator)) return []
  try {
    const ports = await navigator.serial.getPorts()
    return ports.map((port, i) => {
      let label = `Printer ${i + 1}`
      try {
        const info = port.getInfo()
        if (info.usbVendorId != null && info.usbProductId != null) {
          label = `USB Printer ${i + 1}`
        } else {
          label = `Bluetooth Printer ${i + 1}`
        }
      } catch (_) {}
      return { port, label }
    })
  } catch (_) {
    return []
  }
}

/**
 * Sambungkan ke port yang sudah dipilih (dari getPorts) - tanpa buka dialog browser
 * @param {SerialPort} port - dari getAvailablePrinterPorts()
 */
export async function connectThermalPrinterWithPort(port) {
  if (!port || typeof port.open !== 'function') throw new Error('Port tidak valid')
  await port.open({ baudRate: 9600 })
  try {
    const info = port.getInfo()
    if (info.usbVendorId != null && info.usbProductId != null) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        vendorId: info.usbVendorId,
        productId: info.usbProductId,
      }))
    }
  } catch (_) {}
  await port.close().catch(() => {})
  return port
}

/**
 * Sambungkan printer - tampilkan dialog konfirmasi dulu, lalu minta user pilih device
 * Panggil dari tombol "Printer" di halaman POS
 * @param {Object} options - { skipConfirm: true } untuk lewati konfirmasi (modal sudah ditampilkan di POS)
 */
export async function connectThermalPrinter(options = {}) {
  if (!('serial' in navigator)) {
    throw new Error('Web Serial tidak didukung. Gunakan Chrome/Edge.')
  }
  if (!options.skipConfirm) {
    const confirmed = window.confirm(
      'Pilih printer thermal yang akan disambungkan. Jendela pemilihan device akan muncul.'
    )
    if (!confirmed) return null
  }

  const port = await navigator.serial.requestPort()
  await port.open({ baudRate: 9600 })
  const info = port.getInfo()
  if (info.usbVendorId && info.usbProductId) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        vendorId: info.usbVendorId,
        productId: info.usbProductId,
      }))
    } catch (_) {}
  }
  return port
}

/**
 * Generate struk dalam format ESC/POS
 */
function getReceiptChunks(sale) {
  const items = (sale.details || []).map(d => {
    const name = (d.product?.name || 'Produk').substring(0, 24)
    const qty = Number(d.quantity)
    const unit = d.unit?.name || ''
    const price = Number(d.price)
    const subtotal = Number(d.subtotal)
    return { name, qty, unit, price, subtotal }
  })

  const subtotalVal = Number(sale.subtotal || 0)
  const discountVal = Number(sale.discount || 0)
  const taxVal = Number(sale.tax || 0)
  const totalVal = Number(sale.total || 0)
  const paidVal = Number(sale.paid || 0)
  const changeVal = Number(sale.change || 0)

  const encoder = new TextEncoder()
  const chunks = []

  chunks.push(new Uint8Array([0x1b, 0x40])) // ESC @ Init
  chunks.push(encoder.encode('\n'))
  chunks.push(encoder.encode('========================\n'))
  chunks.push(encoder.encode('      STRUK PEMBAYARAN\n'))
  chunks.push(encoder.encode('========================\n'))
  chunks.push(encoder.encode('\n'))
  chunks.push(encoder.encode(`Invoice: ${sale.invoice_number || '-'}\n`))
  chunks.push(encoder.encode(`Tanggal: ${formatDateTime(sale.sale_date || sale.created_at)}\n`))
  chunks.push(encoder.encode(`Kasir: ${sale.user?.name || '-'}\n`))
  chunks.push(encoder.encode('\n'))
  chunks.push(encoder.encode('------------------------\n'))
  chunks.push(encoder.encode('ITEM\n'))
  chunks.push(encoder.encode('------------------------\n'))

  for (const i of items) {
    chunks.push(encoder.encode(`${i.name}\n`))
    chunks.push(encoder.encode(`${i.qty} ${i.unit} x ${formatCurrency(i.price)}\n`))
    chunks.push(encoder.encode(`                     ${formatCurrency(i.subtotal)}\n`))
  }

  chunks.push(encoder.encode('------------------------\n'))
  chunks.push(encoder.encode(`Subtotal:              ${formatCurrency(subtotalVal)}\n`))
  if (discountVal > 0) {
    chunks.push(encoder.encode(`Diskon:                -${formatCurrency(discountVal)}\n`))
  }
  if (taxVal > 0) {
    chunks.push(encoder.encode(`Pajak:                 ${formatCurrency(taxVal)}\n`))
  }
  chunks.push(encoder.encode('------------------------\n'))
  chunks.push(encoder.encode(`TOTAL:                 ${formatCurrency(totalVal)}\n`))
  chunks.push(encoder.encode(`Bayar:                 ${formatCurrency(paidVal)}\n`))
  if (changeVal > 0) {
    chunks.push(encoder.encode(`Kembalian:             ${formatCurrency(changeVal)}\n`))
  }
  if (sale.payment_method === 'credit' && paidVal < totalVal) {
    chunks.push(encoder.encode(`Sisa Utang:            ${formatCurrency(totalVal - paidVal)}\n`))
  }
  chunks.push(encoder.encode(`Metode: ${formatPaymentMethod(sale.payment_method)}\n`))
  if (sale.customer_name) chunks.push(encoder.encode(`Pelanggan: ${sale.customer_name}\n`))
  if (sale.customer_phone) chunks.push(encoder.encode(`No. HP: ${sale.customer_phone}\n`))
  chunks.push(encoder.encode('\n'))
  chunks.push(encoder.encode('========================\n'))
  chunks.push(encoder.encode('   Terima kasih atas\n'))
  chunks.push(encoder.encode('     kunjungan Anda\n'))
  chunks.push(encoder.encode('========================\n'))
  chunks.push(encoder.encode('\n\n'))

  chunks.push(new Uint8Array([0x1b, 0x64, 0x02])) // ESC d 2 - feed 2 lines
  chunks.push(new Uint8Array([0x1d, 0x56, 0x00])) // GS V 0 - full cut

  return chunks
}

/**
 * Generate receipt text lines (untuk print sistem)
 */
function getReceiptLines(sale) {
  const items = (sale.details || []).map(d => {
    const name = (d.product?.name || 'Produk').substring(0, 24)
    const qty = Number(d.quantity)
    const unit = d.unit?.name || ''
    const price = Number(d.price)
    const subtotal = Number(d.subtotal)
    return { name, qty, unit, price, subtotal }
  })
  const subtotalVal = Number(sale.subtotal || 0)
  const discountVal = Number(sale.discount || 0)
  const taxVal = Number(sale.tax || 0)
  const totalVal = Number(sale.total || 0)
  const paidVal = Number(sale.paid || 0)
  const changeVal = Number(sale.change || 0)

  const lines = [
    '========================',
    '      STRUK PEMBAYARAN',
    '========================',
    '',
    `Invoice: ${sale.invoice_number || '-'}`,
    `Tanggal: ${formatDateTime(sale.sale_date || sale.created_at)}`,
    `Kasir: ${sale.user?.name || '-'}`,
    '',
    '------------------------',
    'ITEM',
    '------------------------',
    ...items.flatMap(i => [
      i.name,
      `${i.qty} ${i.unit} x ${formatCurrency(i.price)}`,
      `                     ${formatCurrency(i.subtotal)}`,
    ]),
    '------------------------',
    `Subtotal:              ${formatCurrency(subtotalVal)}`,
  ]
  if (discountVal > 0) lines.push(`Diskon:                -${formatCurrency(discountVal)}`)
  if (taxVal > 0) lines.push(`Pajak:                 ${formatCurrency(taxVal)}`)
  lines.push(
    '------------------------',
    `TOTAL:                 ${formatCurrency(totalVal)}`,
    `Bayar:                 ${formatCurrency(paidVal)}`
  )
  if (changeVal > 0) lines.push(`Kembalian:             ${formatCurrency(changeVal)}`)
  if (sale.payment_method === 'credit' && paidVal < totalVal) {
    lines.push(`Sisa Utang:            ${formatCurrency(totalVal - paidVal)}`)
  }
  lines.push(`Metode: ${formatPaymentMethod(sale.payment_method)}`)
  if (sale.customer_name) lines.push(`Pelanggan: ${sale.customer_name}`)
  if (sale.customer_phone) lines.push(`No. HP: ${sale.customer_phone}`)
  lines.push('', '========================', '   Terima kasih atas', '     kunjungan Anda', '========================')
  return lines
}

/**
 * Cetak via dialog print sistem (untuk printer XP58 yang sudah diatur di Mac/Windows)
 * User pilih printer di dialog, cocok untuk printer yang tidak muncul di Web Serial
 */
function printViaSystemDialog(sale) {
  const lines = getReceiptLines(sale)
  const text = lines.join('\n')
  const safeHtml = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\n/g, '<br>')
  const html = `<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Struk - ${(sale.invoice_number || '').replace(/</g, '&lt;')}</title>
<style>
@media print{body{margin:0;padding:0}.no-print{display:none!important}}
body{font-family:'Courier New',monospace;font-size:12px;width:80mm;max-width:80mm;margin:0 auto;padding:8px;line-height:1.3}
</style></head>
<body>
<div>${safeHtml}</div>
<div class="no-print" style="margin-top:16px;text-align:center">
  <button onclick="window.print()" style="padding:8px 16px;cursor:pointer">Cetak</button>
  <button onclick="window.close()" style="padding:8px 16px;margin-left:8px;cursor:pointer">Tutup</button>
</div>
<script>window.onload=function(){window.print()}<\/script>
</body></html>`
  const w = window.open('', '_blank', 'width=400,height=600')
  if (w) {
    w.document.write(html)
    w.document.close()
  } else {
    const blob = new Blob([html], { type: 'text/html' })
    const url = URL.createObjectURL(blob)
    const w2 = window.open(url, '_blank')
    if (w2) w2.onload = () => w2.print()
    setTimeout(() => URL.revokeObjectURL(url), 5000)
  }
}

/**
 * Cetak struk ke printer thermal
 * - Coba Web Serial (USB/Bluetooth serial) dulu
 * - Jika gagal: tawarkan cetak via dialog sistem (pilih XP58 di Mac/Windows)
 * @param {Object} sale - Objek transaksi dengan details
 * @param {Object} options - { detectPrinter: true } untuk deteksi printer USB/Bluetooth dulu
 */
export async function printReceipt(sale, options = {}) {
  // 1. Coba Web Serial
  try {
    const device = await connect(options)
    if (!device) return { success: false, cancelled: true }
    try {
      await device.open()
      const data = getReceiptChunks(sale)
      for (const chunk of data) {
        await device.send(chunk)
      }
      return { success: true, method: 'escpos' }
    } finally {
      await device.close().catch(() => {})
    }
  } catch (err) {
    // Web Serial gagal atau tidak didukung - tawarkan cetak via printer sistem
    const msg = !('serial' in navigator)
      ? 'Web Serial tidak didukung di browser ini.'
      : 'Printer tidak terdeteksi via USB/Bluetooth serial.'
    const useSystem = window.confirm(
      msg + '\n\n' +
      'Gunakan printer sistem (XP58 yang sudah diatur di Mac)?\n' +
      'Dialog print akan muncul - pilih printer XP58 lalu cetak.'
    )
    if (useSystem) {
      printViaSystemDialog(sale)
      return { success: true, method: 'system' }
    }
    throw err
  }
}
