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
        Schema::create('asset_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets')->noActionOnDelete();
            $table->uuid('from_user_id')->nullable();
            $table->foreign('from_user_id')->references('id')->on('users')->noActionOnDelete();
            $table->uuid('to_user_id');
            $table->foreign('to_user_id')->references('id')->on('users')->noActionOnDelete();
            $table->timestamp('sent_at', 6);
            $table->timestamp('confirmed_at', 6)->nullable();
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
        Schema::dropIfExists('asset_histories');
    }
};
