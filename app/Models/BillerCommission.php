<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillerCommission extends Model
{
    protected $fillable = [
        'sale_id',
        'biller_id',
        'total_items',
        'total_profit',
        'commission_amount',
        'paid_amount',
        'is_paid',
        'calculated_at',
        'paid_at'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function biller()
    {
        return $this->belongsTo(Biller::class);
    }
}
