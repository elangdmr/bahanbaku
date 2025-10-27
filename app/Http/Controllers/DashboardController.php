<?php

namespace App\Http\Controllers;

use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // ===== Hitung akun per role =====
        $adminCount      = User::where('role', 'Admin')->count();
        $ppicCount       = User::where('role', 'PPIC')->count();
        $rndCount        = User::where('role', 'R&D')->count();
        $purchasingCount = User::where('role', 'Purchasing')->count();

        // Total user (kalau masih dipakai di blade)
        $user = $adminCount + $ppicCount + $rndCount + $purchasingCount;

        // ===== Angka kartu kecil di atas =====
        // Sesuaikan dari tabel request-mu kalau sudah ada.
        $requests = 0;
        $approved = 0;
        $pending  = 0;

        // Kompat untuk blade lama yang masih pakai $kelas & $pelajaran
        $kelas     = $requests;
        $pelajaran = $approved;

        // Kompat untuk ekspresi: {{ $pending ?? $userSiswa ?? 0 }}
        $userSiswa = 0;

        return view('home.dashboard', compact(
            'user',
            'adminCount',
            'ppicCount',
            'rndCount',
            'purchasingCount',
            'requests',
            'approved',
            'pending',
            'kelas',
            'pelajaran',
            'userSiswa'
        ));
    }
}
