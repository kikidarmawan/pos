<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $sales;

    public function __construct($sales)
    {
        $this->sales = $sales;
    }

    public function collection()
    {
        return $this->sales;
    }

    public function headings(): array
    {
        return [
            'No Invoice',
            'Tanggal',
            'Pelanggan',
            'Gudang',
            'Kasir',
            'Subtotal',
            'Diskon',
            'Pajak',
            'Total',
            'Dibayar',
            'Kembalian',
            'Metode Pembayaran',
            'Status',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->invoice_number,
            $sale->sale_date->format('d/m/Y'),
            $sale->customer_name ?? 'Umum',
            $sale->warehouse->name ?? '-',
            $sale->user->name ?? '-',
            $sale->subtotal,
            $sale->discount,
            $sale->tax,
            $sale->total,
            $sale->paid,
            $sale->change,
            $sale->payment_method === 'credit' ? 'Utang' : strtoupper($sale->payment_method),
            strtoupper($sale->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
