<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasingVendor extends Model
{
    use HasFactory;

    // Jika tabelmu bernama 'purchasing_vendor' (singular, underscore),
    // set manual. Kalau pakai default plural 'purchasing_vendors', hapus baris ini.
    protected $table = 'purchasing_vendor';

    // Biar aman dari MassAssignmentException saat create/update
    protected $guarded = [];

    // Kalau ada kolom tanggal kustom, tinggal tambahkan di $casts
    protected $casts = [
        'approved_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];
}
