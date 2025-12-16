<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApartmentImage extends Model
{
    protected $table = 'apartment_images';

    protected $guarded = [];

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }
}
