<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    protected $table = 'sales_invoices';
    protected $fillable = [
        'customer_id',
        'payment_id',
        'number_invoice',
        'total',
        'total_paid_dp',
        'other_expenses',
        'other_expenses_status',
        'other_expenses_price',
        'other_expenses_description',
        'other_expenses_percent_admin',
        'date',
        'due_date',
        'paid_date',
        'paid_description',
        'status',
    ];

    public function product()
    {
        return $this->hasMany('App\Models\Product', 'id', 'product_id');
    }

    public function customer()
    {
        return $this->belongsTo('App\Models\Customer', 'customer_id', 'id');
    }

    public function order_item()
    {
        return $this->hasMany('App\Models\OrderItem', 'sales_invoice_number', 'number_invoice');
    }

    public function payment_method()
    {
        return $this->belongsTo('App\Models\PaymentMethod', 'payment_id', 'id');
    }

}
