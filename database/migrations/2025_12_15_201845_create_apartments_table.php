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
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('country');
            $table->string('city');
            $table->text('address')->nullable();
            $table->decimal('price_per_night', 10, 2); // ديسيمال مشان نحسن نحط كسور بالسعر ومسموح فقط رقمين بعد الفاصلة
            $table->integer('max_guests')->default(2);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
