<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    protected $table = 'produks';
    protected $fillable = ['kode','nama','brand','deskripsi'];

    public function bahans(): BelongsToMany
    {
        return $this->belongsToMany(Bahan::class, 'produk_bahan', 'produk_id', 'bahan_id')
            ->withPivot(['qty','satuan','peran','urutan'])
            ->withTimestamps()
            ->orderBy('produk_bahan.urutan');
    }

    public function permintaanBahan(): HasMany
    {
        return $this->hasMany(PermintaanBahan::class, 'produk_id');
    }
}
