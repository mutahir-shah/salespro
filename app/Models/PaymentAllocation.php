<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use HasFactory;
    protected $fillable = [
        "purchase_id",
        "payment_id",
        "allocated_amount"
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
