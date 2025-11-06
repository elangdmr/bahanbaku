<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PermintaanBahan extends Model
{
    use HasFactory;

    protected $table = 'permintaan_bahan';

    protected $fillable = [
        'produk_id',              // <-- penting untuk mapping ke master produk
        'bahan_id',
        'jumlah',
        'satuan',
        'kategori',
        'tanggal_kebutuhan',
        'alasan',
        'status',
        'user_id',
    ];

    protected $casts = [
        'tanggal_kebutuhan' => 'date:Y-m-d',
    ];

    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function produk()
    {
        // withDefault agar tidak error saat produk_id null / belum di-set
        return $this->belongsTo(Produk::class, 'produk_id')->withDefault();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
