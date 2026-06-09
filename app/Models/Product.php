<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'category',
        'price',
        'description',
        'sku_code',
        'quantity',
        'status',
        'shopify_id',
        'auth_id',
        'move_to_shopify',
    ];
}