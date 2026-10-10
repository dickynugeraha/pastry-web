<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'qty',
        'price',
        'subtotal',
        'product_name_snapshot',
        'product_image_snapshot',
    ];

    protected function order()
    {
        return $this->belongsTo(Order::class);
    }

    protected function product()
    {
        return $this->belongsTo(Product::class);
    }
}
