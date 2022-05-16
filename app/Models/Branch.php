<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function manager()
    {
        return $this->belongsTo(user::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // public function scopeOrders($query)
    // {
    //     return $query->get();
    // }
}
