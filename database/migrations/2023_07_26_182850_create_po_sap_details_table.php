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
        Schema::create('po_sap_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('po_sap_id');
            $table->foreign('po_sap_id')->references('id')->on('po_saps')->cascadeOnDelete();
            $table->string('number', 64);
            $table->string('name', 64);
            $table->string('uom', 32);
            $table->string('currency', 32);
            $table->double('qty', 17, 2)->default(0.00);
            $table->double('price', 17, 2)->default(0.00);
            $table->double('rate', 17, 2)->default(0.00);
            $table->double('idr_price', 17, 2)->default(0.00);
            $table->double('total', 17, 2)->default(0.00);

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
        Schema::dropIfExists('po_sap_details');
    }
};
