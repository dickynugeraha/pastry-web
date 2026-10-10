<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'product_images';
    protected $fillable = ['product_id', 'path', 'is_primary', 'sort_order'];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    //pastikan hanya satu gambar primary didalam satu produk
    protected static function booted()
    {
        static::saving(function ($image) {
            if ($image->is_primary && $image->product_id) {
                // Set all other images of the same product to not primary
                static::where('product_id', $image->product_id)
                    ->where('id', '!=', $image->id ?? 0)
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }
}
