<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $products;

    public function __construct($products)
    {
        $this->products = $products;
    }

    public function collection()
    {
        return $this->products;
    }

    public function headings(): array
    {
        return [
            'Kode',
            'Nama Produk',
            'Kategori',
            'Satuan',
            'Harga',
            'Stok',
            'Min. Stok',
            'Nilai Stok',
            'Status',
        ];
    }

    public function map($product): array
    {
        $totalStock = $product->total_stock ?? 0;
        $stockValue = $totalStock * $product->base_price;
        $status = $totalStock < $product->minimum_stock ? 'RENDAH' : 'NORMAL';

        return [
            $product->code,
            $product->name,
            $product->category->name ?? '-',
            $product->baseUnit->name ?? '-',
            $product->base_price,
            $totalStock,
            $product->minimum_stock,
            $stockValue,
            $status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
