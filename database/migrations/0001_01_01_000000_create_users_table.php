    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        public function up()
        {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('P_ID')->nullable();
                $table->text('address')->nullable();
                $table->date('birthday')->nullable();
                $table->enum('gender', ['male', 'female', 'other'])->nullable();
                $table->string('p_image')->nullable();
                $table->string('role');
                $table->timestamps();
            });
        }

        public function down()
        {
            Schema::dropIfExists('users');
        }
    };
