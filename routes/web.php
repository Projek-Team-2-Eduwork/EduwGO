<?php

use App\Http\Controllers\Admin\PesananController;
use App\Http\Controllers\BookingPaymentController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\KendaraanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

// ==========================================
// Rute Publik Terbuka
// ==========================================

Route::get('/', function () {
    return view('welcome');
});

// Halaman Publik: Detail Kendaraan
Route::get('/kendaraan/{vehicle}', [VehicleController::class, 'show'])->name('kendaraan.detail');

// API Internal: Opsi durasi sewa per unit untuk modal pilih durasi (EG-11)
Route::get('/api/kendaraan/{vehicle}/durasi', [KendaraanController::class, 'durasi'])
    ->name('kendaraan.durasi');

// Webhook Xendit (EG-14): Endpoint eksternal, diverifikasi via x-callback-token, dikecualikan CSRF
Route::post('/webhooks/xendit', [XenditWebhookController::class, 'handle'])
    ->name('xendit.webhook');

// ==========================================
// Rute Development
// ==========================================

// Referensi visual token/komponen EduwGo (EG-7, EG-8), tidak tersedia di production.
if (! app()->isProduction()) {
    Route::get('/styleguide', function () {
        return view('styleguide');
    })->name('styleguide');
}

// ==========================================
// Rute Area Pengguna Terautentikasi
// ==========================================

// Dashboard Pengguna Umum
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Manajemen Profil (Hanya butuh login)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    // Alias sesuai spesifikasi EG-28 (Figma: Profil > Home).
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit.indonesian');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Preferensi tema (Dark/Light mode), juga dipakai theme-toggle di navbar.
    Route::patch('/profil/tema', [ProfileController::class, 'theme'])->name('profile.theme');
});

// Pesanan & Pembayaran (Wajib login dan email terverifikasi) (EG-22)
Route::middleware(['auth', 'verified'])->group(function () {
    // Alur pembayaran Xendit
    Route::get('/pesanan/{code}/menunggu', [BookingPaymentController::class, 'waiting'])->name('booking.waiting');

    // Placeholder route untuk pesanan sukses dan gagal (EG-15)
    Route::get('/pesanan/{code}/sukses', [BookingPaymentController::class, 'success'])->name('booking.success');
    Route::get('/pesanan/{code}/gagal', [BookingPaymentController::class, 'failed'])->name('booking.failed');

    // Fitur Booking Ulang satu klik untuk pesanan expired (EG-27)
    Route::get('/pesanan/{code}/ulang', [BookingPaymentController::class, 'ulang'])->name('booking.rebook');
});

// Alur Checkout Sewa Kendaraan
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/checkout/{vehicle}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/{vehicle}', [CheckoutController::class, 'store'])->name('checkout.store');
});

// ==========================================
// Rute Area Admin
// ==========================================

// Grup Admin (Wajib login, terverifikasi, dan memiliki role admin)
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Placeholder, dashboard admin sebenarnya dibuat di EG-8 (F-7) / Alur B
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Detail pesanan + sinkronisasi manual status invoice Xendit (EG-26)
    Route::get('/pesanan/{code}', [PesananController::class, 'show'])->name('pesanan.show');
    Route::get('/pesanan/{code}/cek-status', [PesananController::class, 'cekStatus'])->name('pesanan.cek-status');
});

// Memuat rute-rute bawaan Laravel Breeze
require __DIR__.'/auth.php';
