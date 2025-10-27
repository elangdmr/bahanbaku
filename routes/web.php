<?php

use Illuminate\Support\Facades\Route;

/* ===== Controller imports (only the ones we actually use) ===== */
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\UserManagement\UserController;

use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\PurchasingVendorController;
use App\Http\Controllers\UjiCoaController;
use App\Http\Controllers\HalalController;
use App\Http\Controllers\SamplingPchController;
use App\Http\Controllers\TrialRndController;
use App\Http\Controllers\RegistrasiController;
use App\Http\Controllers\BahanController;
use App\Http\Controllers\RiwayatController;

/* ====================================================================
 * AUTH
 * ==================================================================== */
Route::get('/login',  [LoginController::class, 'login'])->name('login');
Route::post('/login', [LoginController::class, 'store']);

/* ====================================================================
 * PROTECTED AREA
 * ==================================================================== */
Route::middleware(['auth'])->group(function () {

    // === DASHBOARD (semua role) ===
    Route::get('/', fn () => redirect()->route('dashboard'))->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* ======================= HALAL (Admin + PPIC) ======================= */
    Route::prefix('halal')->name('halal.')
        ->middleware('role:Admin,PPIC')
        ->group(function () {
            Route::get('/',             [HalalController::class, 'index'])->name('index');
            Route::get('/{id}/edit',    [HalalController::class, 'edit'])->name('edit');
            Route::put('/{id}',         [HalalController::class, 'update'])->name('update');
            Route::get('/{id}/confirm', [HalalController::class, 'confirmForm'])->name('confirm.form');
            Route::put('/{id}/confirm', [HalalController::class, 'confirmUpdate'])->name('confirm.update');
        });

    /* =================== PURCHASING (Admin + Purchasing) =================== */
    Route::prefix('purchasing-vendor')->name('purch-vendor.')
        ->middleware('role:Admin,Purchasing')
        ->group(function () {
            Route::get('/',                     [PurchasingVendorController::class, 'index'])->name('index');
            Route::get('/{id}/edit',            [PurchasingVendorController::class, 'edit'])->name('edit');
            Route::put('/{id}',                 [PurchasingVendorController::class, 'update'])->name('update');
            Route::get('/{id}/accept',          [PurchasingVendorController::class, 'acceptForm'])->name('accept.form');
            Route::put('/{id}/accept',          [PurchasingVendorController::class, 'acceptUpdate'])->name('accept.update');
        });

    /* =================== SAMPLING (Admin + Purchasing) =================== */
    Route::prefix('sampling-pch')->name('sampling-pch.')
        ->middleware('role:Admin,Purchasing')
        ->group(function () {
            Route::get('/',                     [SamplingPchController::class, 'index'])->name('index');
            Route::get('/{id}/edit',            [SamplingPchController::class, 'edit'])->name('edit');
            Route::put('/{id}',                 [SamplingPchController::class, 'update'])->name('update');
            Route::get('/{id}/confirm',         [SamplingPchController::class, 'confirmForm'])->name('confirm.form');
            Route::put('/{id}/confirm',         [SamplingPchController::class, 'confirmUpdate'])->name('confirm.update');
            Route::put('/{id}/process-inline',  [SamplingPchController::class, 'processInline'])->name('process.inline');
        });

    /* ======================= RIWAYAT (semua user) ======================= */
Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');
Route::get('/riwayat/{kode}/show', [RiwayatController::class, 'show'])->name('riwayat.show'); // halaman web
Route::get('/riwayat/{type}/{id}/detail', [RiwayatController::class, 'detail'])->name('riwayat.detail'); // PDF


    /* ======================= ADMIN + R&D (modul kerja) ======================= */
    Route::middleware('role:Admin,R&D')->group(function () {

        /* ---------- PERMINTAAN BAHAN BAKU ---------- */
        Route::get('/permintaan-bahan-baku',               [BahanBakuController::class, 'index'])->name('show-permintaan');
        Route::get('/permintaan-bahan-baku/create',        [BahanBakuController::class, 'create'])->name('permintaan.create');
        Route::post('/permintaan-bahan-baku',              [BahanBakuController::class, 'store'])->name('permintaan.store');
        Route::get('/permintaan-bahan-baku/{id}/edit',     [BahanBakuController::class, 'edit'])->name('edit-permintaan');
        Route::put('/permintaan-bahan-baku/{id}',          [BahanBakuController::class, 'update'])->name('update-permintaan');
        Route::put('/permintaan-bahan-baku/{id}/accept',   [BahanBakuController::class, 'accept'])->name('accept-permintaan');

        /* ---------- UJI COA ---------- */
        Route::prefix('uji-coa')->name('uji-coa.')->group(function () {
            Route::get('/',             [UjiCoaController::class, 'index'])->name('index');
            Route::get('/{id}/edit',    [UjiCoaController::class, 'edit'])->name('edit');
            Route::put('/{id}',         [UjiCoaController::class, 'update'])->name('update');
            Route::get('/{id}/confirm', [UjiCoaController::class, 'confirmForm'])->name('confirm.form');
            Route::put('/{id}/confirm', [UjiCoaController::class, 'confirmUpdate'])->name('confirm.update');
        });

        /* ---------- TRIAL R&D ---------- */
        Route::prefix('trial-rnd')->name('trial-rnd.')->group(function () {
            Route::get('/',             [TrialRndController::class, 'index'])->name('index');
            Route::get('/{id}/edit',    [TrialRndController::class, 'edit'])->name('edit');
            Route::put('/{id}',         [TrialRndController::class, 'update'])->name('update');
            Route::get('/{id}/confirm', [TrialRndController::class, 'confirmForm'])->name('confirm.form');
            Route::put('/{id}/confirm', [TrialRndController::class, 'confirmUpdate'])->name('confirm.update');
            Route::put('/{id}/add-qty', [TrialRndController::class, 'addQty'])->name('add-qty'); // kirim balik qty ke Sampling
        });

        /* ---------- REGISTRASI NIE ---------- */
        Route::prefix('registrasi')->name('registrasi.')->group(function () {
            Route::get('/',             [RegistrasiController::class, 'index'])->name('index');
            Route::get('/{id}/edit',    [RegistrasiController::class, 'edit'])->name('edit');
            Route::put('/{id}',         [RegistrasiController::class, 'update'])->name('update');
            Route::get('/{id}/confirm', [RegistrasiController::class, 'confirmForm'])->name('confirm.form');
            Route::put('/{id}/confirm', [RegistrasiController::class, 'confirmUpdate'])->name('confirm.update');
        });
    });

    /* ====================================================================
     * AREA ADMIN (global access)
     * ==================================================================== */
    Route::middleware(['isGlobalAccess'])->group(function () {

        /* ===================== USER MANAGEMENT (ADMIN) ===================== */
        Route::middleware(['admin'])->group(function () {

            // R&D
            Route::get('/show-rnd',                [UserController::class, 'showRND'])->name('show-rnd');
            Route::post('/show-rnd',               [UserController::class, 'storeRND']);
            Route::get('/show-rnd/{id}/edit',      [UserController::class, 'editRND'])->name('edit-rnd');
            Route::put('/show-rnd/{id}',           [UserController::class, 'updateRND']);
            Route::delete('/show-rnd/{id}',        [UserController::class, 'destroy'])->name('delete-rnd');

            // PPIC
            Route::get('/show-ppic',               [UserController::class, 'showPPIC'])->name('show-ppic');
            Route::post('/show-ppic',              [UserController::class, 'storePPIC']);
            Route::get('/show-ppic/{id}/edit',     [UserController::class, 'editPPIC'])->name('edit-ppic');
            Route::put('/show-ppic/{id}',          [UserController::class, 'updatePPIC']);
            Route::delete('/show-ppic/{id}',       [UserController::class, 'destroy'])->name('delete-ppic');

            // Purchasing
            Route::get('/show-purchasing',         [UserController::class, 'showPurchasing'])->name('show-purchasing');
            Route::post('/show-purchasing',        [UserController::class, 'storePurchasing']);
            Route::get('/show-purchasing/{id}/edit',[UserController::class, 'editPurchasing'])->name('edit-purchasing');
            Route::put('/show-purchasing/{id}',    [UserController::class, 'updatePurchasing']);
            Route::delete('/show-purchasing/{id}', [UserController::class, 'destroy'])->name('delete-purchasing');

            /* ================= MASTER DATA (khusus admin) ================= */
            Route::resource('master-bahan', BahanController::class)
                ->parameters(['master-bahan' => 'bahan'])
                ->names('bahan');
        });
    });

    /* ===== AUTH & PROFILE ===== */
    Route::post('/logout', [UserController::class, 'logout'])->name('logout');

    Route::get('show-profile',            [UserController::class, 'profile'])->name('show-profile');
    Route::put('show-profile/general',    [UserController::class, 'updateGeneral'])->name('edit-general');
    Route::put('show-profile',            [UserController::class, 'updatePassword'])->name('edit-password');
});
