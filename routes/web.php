<?php

use App\Http\Controllers\BookingPaymentController;
use App\Http\Controllers\KendaraanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    // Alias sesuai spesifikasi EG-28 (Figma: Profil > Home).
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit.indonesian');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Preferensi tema, juga dipakai theme-toggle di navbar.
    Route::patch('/profil/tema', [ProfileController::class, 'theme'])->name('profile.theme');

    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Alur pembayaran Xendit
    Route::get('/pesanan/{code}/menunggu', [BookingPaymentController::class, 'waiting'])->name('booking.waiting');

    // Placeholder route untuk EG-15
    Route::get('/pesanan/{code}/sukses', [BookingPaymentController::class, 'success'])->name('booking.success');
    Route::get('/pesanan/{code}/gagal', [BookingPaymentController::class, 'failed'])->name('booking.failed');
});

// Opsi durasi sewa per unit untuk modal pilih durasi (EG-11)
Route::get('/api/kendaraan/{vehicle}/durasi', [KendaraanController::class, 'durasi'])
    ->name('kendaraan.durasi');

// Webhook Xendit (EG-14): di luar auth, diverifikasi via x-callback-token, dikecualikan CSRF
Route::post('/webhooks/xendit', [XenditWebhookController::class, 'handle'])
    ->name('xendit.webhook');

require __DIR__.'/auth.php';
