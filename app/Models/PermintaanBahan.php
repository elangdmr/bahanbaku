<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanBahan extends Model
{
    protected $table = 'permintaan_bahan';

    protected $fillable = [
        'bahan_id','jumlah','satuan','kategori','tanggal_kebutuhan','alasan','status','user_id'
    ];

    public function bahan()
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
