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
        Schema::create('request_asset_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('request_asset_id');
            $table->foreign('request_asset_id')->references('id')->on('request_assets')->cascadeOnDelete();
            $table->uuid('sub_category_id');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->cascadeOnDelete();
            $table->text('description');
            $table->bigInteger('qty');
            $table->string('uom', 32);
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
        Schema::dropIfExists('request_asset_details');
    }
};
