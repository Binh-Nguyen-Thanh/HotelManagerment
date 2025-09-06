<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 50)->index();
            $table->enum('payment_method', ['vnpay', 'momo', 'cash']);
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->unique()->nullable();
            $table->enum('status', ['pending', 'success', 'refunded', 'cancel'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
