<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bahan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bahans';
    protected $fillable = ['nama','satuan_default','kategori_default'];

    // Opsi dropdown agar konsisten di form
    public const SATUAN  = ['gr','kg','mg','mcg','mL','L','pcs'];
    public const KATEGORI = ['Bahan Aktif','Bahan Pembantu'];
}
