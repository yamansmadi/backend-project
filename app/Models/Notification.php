<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $guarded = [];

    //  العلاقات الديناميكية (Polymorphic)
    //  العلاقة مع أي شيء
    // يعني بدال ما اربط الاشعار مع الحجز والرسالة واليوزر والشقة عملت علاقة واحدة مع الكل لانه نفس نوع العلاقة مع الكل
    public function related()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
