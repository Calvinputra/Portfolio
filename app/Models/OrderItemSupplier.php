<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemSupplier extends Model
{
    protected $table = 'order_item_supplier';
    protected $fillable = [
        'purchase_invoice_number',
        'product_id',
        'stock_warehouse_id',
        'quantity',
        'price',
        'total_price',
        'discount',
        'discount_type',
    ];
    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'product_id', 'id');
    }

    public function stock_warehouse()
    {
        return $this->belongsTo('App\Models\StockWarehouse', 'stock_warehouse_id', 'id');
    }

    public function purchase_invoice()
    {
        return $this->belongsTo('App\Models\Purchase_invoice_number', 'purchase_invoice_number', 'number_purchase_invoice');
    }

}
