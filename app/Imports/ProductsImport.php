<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithValidation
{
    public function model(array $row)
    {
        // Try to resolve category by slug or name if provided
        $categoryId = null;
        if (!empty($row['category_slug'])) {
            $category = Category::where('slug', $row['category_slug'])->first();
            $categoryId = $category?->id;
        } elseif (!empty($row['category_name'])) {
            $category = Category::where('name', $row['category_name'])->first();
            $categoryId = $category?->id;
        }

        $data = [
            'name'            => $row['name'] ?? null,
            'description'     => $row['description'] ?? null,
            'price'           => isset($row['price']) ? (float) $row['price'] : null,
            'stock'           => isset($row['stock']) ? (int) $row['stock'] : null,
            'sku'             => $row['sku'] ?? null,
            'barcode'         => $row['barcode'] ?? null,
            'qr_code'         => $row['qr_code'] ?? null,
            'category_id'     => $categoryId,
            'weight_in_grams' => isset($row['weight_in_grams']) ? (float) $row['weight_in_grams'] : null,
            'length_cm'       => isset($row['length_cm']) ? (float) $row['length_cm'] : null,
            'width_cm'        => isset($row['width_cm']) ? (float) $row['width_cm'] : null,
            'height_cm'       => isset($row['height_cm']) ? (float) $row['height_cm'] : null,
            'is_active'       => isset($row['is_active']) ? (int) $row['is_active'] : 1,
        ];

        // Generate slug if not present via model events, else ensure
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Upsert by SKU if provided, else create
        if (!empty($data['sku'])) {
            return Product::updateOrCreate(['sku' => $data['sku']], $data);
        }

        return new Product($data);
    }

    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.price' => ['nullable', 'numeric', 'min:0'],
            '*.stock' => ['nullable', 'integer', 'min:0'],
            '*.sku' => ['nullable', 'string', 'max:255'],
            '*.barcode' => ['nullable', 'string', 'max:255'],
            '*.qr_code' => ['nullable', 'string', 'max:255'],
            '*.weight_in_grams' => ['nullable', 'numeric', 'min:0'],
            '*.length_cm' => ['nullable', 'numeric', 'min:0'],
            '*.width_cm' => ['nullable', 'numeric', 'min:0'],
            '*.height_cm' => ['nullable', 'numeric', 'min:0'],
            '*.is_active' => ['nullable', 'in:0,1'],
        ];
    }
}
