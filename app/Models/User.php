<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'birth_date',
        'profile_image',
        'id_image',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessFilament(): bool
    {
        return $this->email === 'admin@gmail.com';
    }


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime'
        ];
    }

    public function ownedApartments()
    {
        return $this->hasMany(Apartment::class, 'owner_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'tenant_id');
    }

    public function ratings()
    {
        return $this->hasMany(Review::class, 'tenant_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
}
