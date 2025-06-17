<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $fillable = [
        'sku',
        'brand_id',
        'categories_id',
        'classification_id',
        'type',
        'material',
        'colour',
        'cost_price',
        'unit',
        'shape',
        'description',
    ];

    public function category()
    {
        return $this->hasOne('App\Models\Category', 'id', 'categories_id');
    }

    public function classification()
    {
        return $this->hasOne('App\Models\Classification', 'id', 'classification_id');
    }

    public function brand()
    {
        return $this->hasOne('App\Models\Brand', 'id', 'brand_id');
    }

    public function type()
    {
        return $this->belongsTo('App\Models\Type', 'type_id', 'id');
    }

    public function shape()
    {
        return $this->belongsTo('App\Models\Shape', 'shape_id', 'id');
    }

    public function material()
    {
        return $this->belongsTo('App\Models\Material', 'material_id', 'id');
    }

    public function colour()
    {
        return $this->belongsTo('App\Models\Colour', 'colour_id', 'id');
    }

    public function stockWarehouse()
    {
        return $this->hasMany('App\Models\StockWarehouse', 'product_id', 'id');
    }
}
