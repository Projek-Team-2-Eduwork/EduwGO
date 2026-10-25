@extends('errors.minimal', ['code' => '429', 'heading' => 'Terlalu banyak percobaan', 'message' => 'Anda mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar lalu coba lagi.'])

@section('title', 'Terlalu banyak percobaan')
