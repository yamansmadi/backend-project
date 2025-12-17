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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['booking_update', 'message', 'system', 'approval'])->default('system');

            // ↓↓↓ هذي هي العلاقة الديناميكية ↓↓↓
            //من احل مرونة في ربط الإشعارات بأنواع مختلفة
            $table->unsignedBigInteger('related_id')->nullable();    // رقم السجل
            $table->string('related_type')->nullable();              // نوع السجل لمستخدم او شقة او حجز
            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
