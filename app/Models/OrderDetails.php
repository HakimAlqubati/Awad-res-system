<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{
    use HasFactory;
    protected   $fillable = ['product_unit_id', 'created_by', 'product_id', 'order_id', 'qty', 'price', 'updated_at', 'created_at', 'purchase_invoice_id', 'unit_price', 'available_qty'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
