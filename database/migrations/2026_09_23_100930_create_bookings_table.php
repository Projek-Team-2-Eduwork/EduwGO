<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // EG.000001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Motor pakai restrictOnDelete: hapus unit harus lewat soft delete (spec),
            // jadi booking lama tidak ikut terhapus.
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->unsignedTinyInteger('duration_days');
            $table->decimal('price_per_day', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('status')->index();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            // Composite index sesuai permintaan
            $table->index(['vehicle_id', 'status', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
