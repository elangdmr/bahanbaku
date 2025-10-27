<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    use HasFactory;

    // Mapping ke tabel lama
    protected $table = 'permintaan_bahan';

    protected $fillable = [
        'bahan_id',
        'status',              // Proses Uji COA | Approved | Rejected
        'hasil_uji',           // (jika kolom ada)
        'keterangan',
        'mulai_pengujian',     // (jika kolom ada)
        'tgl_coa_diterima',

        // kolom vendor (jika ada di tabel)
        'pabrik_pembuat',
        'negara_asal',
        'distributor',
        'tgl_permintaan_coa',
        'est_coa_diterima',
    ];

    protected $casts = [
        'mulai_pengujian'     => 'date',
        'tgl_coa_diterima'    => 'date',
        'tgl_permintaan_coa'  => 'date',
        'est_coa_diterima'    => 'date',
    ];

    public function bahan()
    {
        return $this->belongsTo(\App\Models\Bahan::class, 'bahan_id');
    }

    public function getKodeAttribute(): string
    {
        $yy = ($this->created_at ?? now())->format('y');
        return 'PB-' . $yy . '.' . $this->id;
    }

    public function scopePending($q)  { return $q->where('status', 'Proses Uji COA'); }
    public function scopeApproved($q) { return $q->where('status', 'Approved'); }
    public function scopeRejected($q) { return $q->where('status', 'Rejected'); }
}
