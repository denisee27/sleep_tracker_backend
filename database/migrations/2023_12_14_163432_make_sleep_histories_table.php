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
        Schema::create('sleep_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamp('sleep_start')->nullable();
            $table->timestamp('sleep_end')->nullable();
            $table->double('sleep_duration',3,1);
            $table->integer('sleep_quality');
            $table->double('daily_scoring',3,1)->nullable();
            $table->string('sleep_result',64)->nullable();
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
        Schema::dropIfExists('sleep_histories');
    }
};
