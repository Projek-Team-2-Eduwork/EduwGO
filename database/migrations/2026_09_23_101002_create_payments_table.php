<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('method'); // xendit_invoice|cash
            $table->string('gateway_reference')->nullable()->index();
            $table->string('gateway_url')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status'); // pending|paid|expired|failed
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
