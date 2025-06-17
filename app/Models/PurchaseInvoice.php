<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $table = 'purchase_invoices';
    protected $fillable = [
        'supplier_id',
        'payment_id',
        'delivery_order_id',
        'number_purchase_invoice',
        'discount_type',
        'discount',
        'total',
        'due_date',
        'paid_date',
        'paid_description',
        'status',
        'other_expenses',
        'other_expenses_status',
        'other_expenses_price',
        'other_expenses_description',
        'date',
        'total_paid_dp',
    ];

    public function product()
    {
        return $this->hasMany('App\Models\Product', 'id', 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Models\Supplier', 'supplier_id', 'id');
    }

    public function order_item_supplier()
    {
        return $this->hasMany('App\Models\OrderItemSupplier', 'purchase_invoice_number', 'number_purchase_invoice');
    }

    public function payment_method()
    {
        return $this->belongsTo('App\Models\PaymentMethod', 'payment_id', 'id');
    }

}
