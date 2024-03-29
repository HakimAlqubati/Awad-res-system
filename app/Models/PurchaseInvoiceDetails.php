<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_at',
        'updated_at',
        'unit_id',
        'product_id',
        'ordered_qty',
        'available_qty'
    ];

    public $timestamps = true;

}
