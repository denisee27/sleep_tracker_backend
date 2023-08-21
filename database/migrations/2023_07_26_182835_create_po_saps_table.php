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
        Schema::create('po_saps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number', 32)->unique();
            $table->date('po_date');
            $table->string('supplier', 64)->nullable();
            $table->string('incoterms', 128)->nullable();
            $table->string('term_of_payment', 128)->nullable();
            $table->uuid('activated_by')->nullable()->index();
            $table->double('idr_rate', 17, 2)->default(0.00);
            $table->date('delivery_date')->nullable();
            $table->boolean('status')->default(0);

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
        Schema::dropIfExists('po_saps');
    }
};
