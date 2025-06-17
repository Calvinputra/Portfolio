<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_item';
    protected $fillable = [
        'sales_invoice_number',
        'purchase_invoice_number',
        'product_id',
        'stock_warehouse_id',
        'quantity',
        'price',
        'total_price',
    ];
    public function product()
    {
        return $this->belongsTo('App\Models\Product', 'product_id', 'id');
    }

    public function stock_warehouse()
    {
        return $this->belongsTo('App\Models\StockWarehouse', 'stock_warehouse_id', 'id');
    }

    public function sales_invoice()
    {
        return $this->belongsTo('App\Models\SalesInvoice', 'sales_invoice_number', 'number_invoice');
    }

}
