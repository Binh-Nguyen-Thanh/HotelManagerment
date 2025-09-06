<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
     public function up()
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên loại phòng
            $table->decimal('price', 10, 2); // Giá phòng
            $table->json('capacity')->nullable(); // Số người tối đa dạng JSON
            $table->json('amenities')->nullable(); // Danh sách tiện ích dạng JSON
            $table->text('image')->nullable(); // Hình ảnh loại phòng
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('room_types');
    }
};