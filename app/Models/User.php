<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Kolom yang boleh di-mass assign.
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'email_verified_at', // biar bisa di-set saat create()
    ];

    /**
     * Relasi ke Kelas (jika dipakai).
     */
    public function kelas()
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * Kolom yang disembunyikan saat serialisasi.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting atribut.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
