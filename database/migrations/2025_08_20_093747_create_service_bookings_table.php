<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('service_booking_code', 50)->unique();
            // amount: mảng số lượng, theo thứ tự tương ứng với service_ids
            $table->json('amount');          // ví dụ: [2, 1, 3]
            // service_ids: mảng id dịch vụ theo thứ tự
            $table->json('service_ids');     // ví dụ: [5, 7, 9]
            $table->unsignedBigInteger('total_price')->default(0);
            $table->enum('payment_method', ['vnpay','momo','cash'])->nullable();
            $table->date('booking_date')->nullable();
            $table->date('come_date')->nullable();
            $table->enum('status', ['pending','success','refunded','cancel','checked', 'comed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_bookings');
    }
};