<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'tenant_id',
        'apartment_id',
        'start_date',
        'end_date',
        'total_nights',
        'total_price',
        'status',
        'payment_status',
    ];


    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class, 'apartment_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
