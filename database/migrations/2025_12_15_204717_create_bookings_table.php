<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('apartment_id')->constrained('apartments')->onDelete('cascade');
        $table->date('start_date');
        $table->date('end_date');
        $table->integer('total_nights')-> nullable();
        $table->decimal('total_price', 10, 2);
        $table->enum('status', ['pending','approved','rejected','cancelled'])->default('pending');
        $table->enum('payment_status', ['pending','paid','refunded'])->default('pending');

        // Indexes مفيدة فعلاً
        $table->index('status');
        $table->index(['apartment_id', 'status']);
        $table->index(['start_date', 'end_date']);

        $table->timestamps();
    });

}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
