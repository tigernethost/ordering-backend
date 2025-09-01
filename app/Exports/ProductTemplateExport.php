<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'name',
            'description',
            'price',
            'stock',
            'sku',
            'barcode',
            'qr_code',
            'category_slug',
            'category_name',
            'weight_in_grams',
            'length_cm',
            'width_cm',
            'height_cm',
            'is_active',
        ];
    }

    public function array(): array
    {
        // Provide a sample row to guide users
        return [[
            'Sample Product',
            'Optional description',
            99.99,
            10,
            'SKU-001',
            '1234567890123',
            'https://example.com/p/sku-001',
            'beverages',
            'Beverages',
            500,
            10,
            5,
            20,
            1,
        ]];
    }
}
