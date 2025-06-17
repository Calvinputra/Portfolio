<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundleProduct extends Model
{
    protected $table = 'product_bundle';
    protected $fillable = [
        'name',
        'product_id',
        'quantity',
        'total_price',
    ];

    public function getProductIdsAttribute()
    {
        return array_map('intval', explode(',', $this->product_id));
    }

    public function products()
    {
        return Product::whereIn('id', $this->product_ids)->get();
    }
}
