<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Store;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;

class ReceiptPrintService
{
    protected string $printerName;

    public function __construct()
    {
        $this->printerName = config('printing.receipt_printer', 'XP-58');
    }

    /**
     * Get print connector based on OS
     */
    protected function getConnector()
    {
        $printer = $this->printerName;
        if (empty($printer)) {
            throw new \RuntimeException('Nama printer belum diatur. Set RECEIPT_PRINTER di .env');
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return new WindowsPrintConnector($printer);
        }
        if (PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux') {
            return new CupsPrintConnector($printer);
        }
        throw new \RuntimeException('Sistem operasi tidak didukung untuk cetak ESC/POS.');
    }

    /**
     * Format currency untuk struk
     */
    protected function formatCurrency(float $value): string
    {
        return 'Rp' . number_format($value, 0, ',', '.');
    }

    /**
     * Format quantity agar tidak menampilkan .000 untuk bilangan bulat
     * Contoh: 1 (bukan 1.000), 1.5 tetap 1.5
     */
    protected function formatQuantity($value): string
    {
        $qty = (float) $value;
        if ($qty == floor($qty) && $qty < 1e10) {
            return (string) (int) $qty;
        }
        return rtrim(rtrim(sprintf('%.4f', $qty), '0'), '.');
    }

    /**
     * Format metode pembayaran
     */
    protected function formatPaymentMethod(string $method): string
    {
        return match ($method) {
            'cash' => 'Tunai',
            'card' => 'Kartu',
            'transfer' => 'Transfer',
            'credit' => 'Utang',
            default => $method,
        };
    }

    /**
     * Cetak struk transaksi ke printer thermal
     */
    public function printReceipt(Sale $sale): void
    {
        $sale->load(['details.product', 'details.unit', 'user']);

        try {
            $connector = $this->getConnector();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Gagal koneksi printer: ' . $e->getMessage());
        }

        $printer = new Printer($connector);

        try {
            $printer->initialize();
            $printer->setJustification(Printer::JUSTIFY_CENTER);

            // Identitas toko - selalu tampilkan
            $store = Store::current();
            $storeName = $store && trim((string) ($store->name ?? '')) !== '' ? trim($store->name) : (config('app.name', 'Toko'));
            $printer->text(mb_substr($storeName, 0, 24) . "\n");
            if ($store && trim((string) ($store->address ?? '')) !== '') {
                $addr = trim($store->address);
                $printer->text(mb_substr($addr, 0, 24) . "\n");
                if (mb_strlen($addr) > 24) {
                    $printer->text(mb_substr($addr, 24, 24) . "\n");
                }
            }
            if ($store && trim((string) ($store->phone ?? '')) !== '') {
                $printer->text(trim($store->phone) . "\n");
            }
            $printer->text("\n");

            $printer->text("========================\n");
            $printer->text("      STRUK PEMBAYARAN\n");
            $printer->text("========================\n\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            $printer->text("Invoice: " . ($sale->invoice_number ?? '-') . "\n");
            $dateTime = $sale->created_at ? $sale->created_at->format('d M Y H:i') : ($sale->sale_date?->format('d M Y') ?? '-');
            $printer->text("Tanggal: {$dateTime}\n");
            $printer->text("Kasir  : " . ($sale->user?->name ?? '-') . "\n\n");
            $printer->text("------------------------\n");
            $printer->text("ITEM\n");
            $printer->text("------------------------\n");

            foreach ($sale->details as $d) {
                $name = mb_substr($d->product?->name ?? 'Produk', 0, 24);
                $printer->text($name . "\n");
                $qtyStr = $this->formatQuantity($d->quantity);
                $unit = $d->unit?->name ?? '';
                $price = $this->formatCurrency((float) $d->price);
                $subtotal = $this->formatCurrency((float) $d->subtotal);
                $itemDiscount = ((float) $d->price * (float) $d->quantity) - (float) $d->subtotal;
                $printer->text("{$qtyStr} {$unit} x {$price}\n");
                if ($itemDiscount > 0.001) {
                    $printer->text("  Diskon: -" . $this->formatCurrency($itemDiscount) . "\n");
                }
                $printer->text(str_pad($subtotal, 32, ' ', STR_PAD_LEFT) . "\n");
            }

            $printer->text("------------------------\n");
            $printer->text("Subtotal:              " . $this->formatCurrency((float) $sale->subtotal) . "\n");
            if ((float) $sale->discount > 0) {
                $printer->text("Diskon:                -" . $this->formatCurrency((float) $sale->discount) . "\n");
            }
            if ((float) $sale->tax > 0) {
                $printer->text("Pajak:                 " . $this->formatCurrency((float) $sale->tax) . "\n");
            }
            $printer->text("------------------------\n");
            $printer->text("TOTAL:                 " . $this->formatCurrency((float) $sale->total) . "\n");
            $printer->text("Bayar:                 " . $this->formatCurrency((float) $sale->paid) . "\n");

            $change = (float) $sale->change;
            if ($change > 0) {
                $printer->text("Kembalian:             " . $this->formatCurrency($change) . "\n");
            }
            if ($sale->payment_method === 'credit' && (float) $sale->paid < (float) $sale->total) {
                $sisa = (float) $sale->total - (float) $sale->paid;
                $printer->text("Sisa Utang:            " . $this->formatCurrency($sisa) . "\n");
            }

            $printer->text("Metode: " . $this->formatPaymentMethod($sale->payment_method) . "\n");
            if ($sale->customer_name) {
                $printer->text("Pelanggan: {$sale->customer_name}\n");
            }
            if ($sale->customer_phone) {
                $printer->text("No. HP: {$sale->customer_phone}\n");
            }

            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("========================\n");
            $printer->text("   Terima kasih atas\n");
            $printer->text("     kunjungan Anda\n");
            $printer->text("========================\n\n\n");

            $printer->feed(2);
            $printer->cut();

            $printer->close();
        } catch (\Throwable $e) {
            $printer->close();
            throw new \RuntimeException('Gagal cetak struk: ' . $e->getMessage());
        }
    }
}
