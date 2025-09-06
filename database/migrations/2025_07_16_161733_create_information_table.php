<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInformationTable extends Migration
{
    public function up()
    {
        Schema::create('information', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('link_address');
            $table->string('phone');
            $table->string('email');
            $table->string('email_password');
            $table->string('logo')->nullable(); // URL ảnh/logo
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('information');
    }
}
