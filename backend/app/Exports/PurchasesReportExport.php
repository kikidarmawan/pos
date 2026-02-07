<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchasesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $purchases;

    public function __construct($purchases)
    {
        $this->purchases = $purchases;
    }

    public function collection()
    {
        return $this->purchases;
    }

    public function headings(): array
    {
        return [
            'No Invoice',
            'Tanggal',
            'Supplier',
            'Gudang',
            'User',
            'Subtotal',
            'Diskon',
            'Pajak',
            'Ongkir',
            'Total',
            'Status',
        ];
    }

    public function map($purchase): array
    {
        return [
            $purchase->invoice_number,
            $purchase->purchase_date->format('d/m/Y'),
            $purchase->supplier->name ?? '-',
            $purchase->warehouse->name ?? '-',
            $purchase->user->name ?? '-',
            $purchase->subtotal,
            $purchase->discount,
            $purchase->tax,
            $purchase->shipping_cost,
            $purchase->total,
            strtoupper($purchase->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
