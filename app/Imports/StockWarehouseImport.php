<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Classification;
use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\Shape;
use App\Models\StockWarehouse;
use App\Models\Type;
use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\ToModel;

use Maatwebsite\Excel\Concerns\WithHeadingRow; 

class StockWarehouseImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $product = Product::where('description', $row['description'])
            ->orWhere('sku', $row['sku'])
            ->first();

        if (!$product) {
            dd('Product Not Found', ['row' => $row]);
        }

        $stockWarehouse = StockWarehouse::where('product_id', $product->id)->first();

        if ($stockWarehouse) {
            $stockWarehouse->update([
                'cost_price' => $row['cost_price'],
            ]);
        } 

        return $stockWarehouse;
    }

    // public function model(array $row)
    // {
    //     // Cari hanya jika cost_price masih NULL atau kosong
    //     if (!isset($row['cost_price']) || empty($row['cost_price'])) {
    //         return null;
    //     }

    //     $stockWarehouse = StockWarehouse::where('product_id', $row['id'])
    //         ->whereNull('cost_price')
    //         ->first();

    //     if ($stockWarehouse) {
    //         $stockWarehouse->update([
    //             'cost_price' => $row['cost_price'],
    //         ]);
    //     }

    //     return $stockWarehouse;
    // }
}
