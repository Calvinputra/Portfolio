<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockWarehouse extends Model
{
    protected $table = 'stock_warehouse';
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'cost_price',
    ];

    // public function product()
    // {
    //     return $this->belongsTo('App\Models\Product', 'product_id', 'id');
    // }

    // public function warehouse()
    // {
    //     return $this->belongsTo('App\Models\Warehouse', 'warehouse_id', 'id');
    // }

    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'product_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Models\Warehouse', 'warehouse_id', 'id');
    }
}
