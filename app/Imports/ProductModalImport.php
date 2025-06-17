<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductModalImport implements ToModel, WithHeadingRow
{
    private $notFoundProducts = []; // Array untuk menyimpan SKU yang tidak ditemukan
    private $rowNumber = 2; // Excel biasanya mulai dari row 2 jika ada heading

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $product = Product::where('description', $row['description'])
            ->orWhere('sku', $row['sku'])
            ->first();

        if (!$product) {
            // Simpan SKU dan deskripsi produk yang tidak ditemukan dengan nomor barisnya
            $this->notFoundProducts[] = [
                'row' => $this->rowNumber,
                'sku' => $row['sku'],
                'description' => $row['description'] ?? 'No Description',
            ];
        } else {
            // Update cost price jika produk ditemukan
            $product->update(['cost_price' => $row['cost_price']]);
        }

        $this->rowNumber++; // Increment row number
    }

    /**
     * Menampilkan daftar SKU yang tidak ditemukan setelah impor selesai
     */
    public function getNotFoundProducts()
    {
        return $this->notFoundProducts;
    }
}
