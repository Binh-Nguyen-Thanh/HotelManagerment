<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('room_id')->constrained();
            $table->foreignId('room_type_id')->constrained();
            $table->json('guest_number')->nullable();
            $table->json('extra_services')->nullable();
            $table->date('booking_date_in');
            $table->date('booking_date_out');
            $table->datetime('check_in');
            $table->datetime('check_out');
            $table->enum('payment_method', ['vnpay', 'momo', 'cash']);
            $table->enum('status', ['pending','success','refunded','cancel','checked_in', 'checked_out'])->default('pending');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('bookings');
    }
};