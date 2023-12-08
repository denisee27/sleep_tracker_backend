<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 128);
            $table->string('password', 64);
            $table->string('name', 128)->nullable();
            $table->uuid('job')->nullable();
            $table->foreign('job')->references('id')->on('job_lists')->cascadeOnDelete();
            $table->date('bod')->nullable();
            $table->string('gender',16)->nullable();
            $table->string('bmi',16)->nullable();
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->longText('profile_pic')->nullable();
            $table->string('verification_key')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps(6);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
