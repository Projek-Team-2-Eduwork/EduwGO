<?php

use App\Http\Controllers\ProfileController;
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
});

require __DIR__.'/auth.php';
