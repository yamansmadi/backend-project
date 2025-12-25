<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    protected $guarded = [];


    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images()
    {
        return $this->hasMany(ApartmentImage::class);
    }

    public function mainImage()
    {
        return $this->hasOne(ApartmentImage::class)->where('is_main', true);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'apartment_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
