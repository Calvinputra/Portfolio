<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Classification;
use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\Shape;
use App\Models\Type;
use Maatwebsite\Excel\Concerns\ToModel;

use Maatwebsite\Excel\Concerns\WithHeadingRow; 

class ProductImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // $brand_id = Brand::where('code', $row['brand_code'])->where('name', $row['brand'])->first()->id;
        $brand = Brand::where('code', $row['brand_code'])->where('name', $row['brand'])->first();
        if (!$brand) {
            dd('brand Not Found', ['row' => $row]);
        }

        $brand_id = $brand ? $brand->id : null;

        $category_id = Category::where('code', $row['category'])->first()->id;
        // $classification_id = Classification::where('code', $row['classification_code'])->where('name', $row['classification'])->first()->id;

        $classification = Classification::where('code', $row['classification_code'])->where('name', $row['classification'])->first();
        if (!$classification) {
            dd('Classification Not Found', ['row' => $row]);
        }

        $classification_id = $classification ? $classification->id : null;

        
        $type_id = !empty($row['type']) ? Type::firstOrCreate(['name' => $row['type'], 'code' => $row['type']])->id : null;

        $material_id = !empty($row['material']) ? Material::firstOrCreate(['name' => $row['material'], 'code' => $row['material_code']])->id : null;

        $colour_id = !empty($row['colour']) ? Colour::firstOrCreate(['name' => $row['colour'], 'code' => $row['colour_code']])->id : null;

        $shape_id = !empty($row['shape']) ? Shape::firstOrCreate(['name' => $row['shape'], 'code' => $row['shape_code']])->id : null;


        return new Product([
            'sku' => $row['sku'] ?? null,
            'brand_id' => $brand_id, 
            'categories_id' => $category_id,
            'classification_id' => $classification_id,
            'type' => $type_id,
            'material' => $material_id,
            'colour' => $colour_id,
            'shape' => $shape_id,
            'cost_price' => $row['price'],
            'unit' => $row['unit'],
            'description' => $row['description'],
        ]);

    }
}
