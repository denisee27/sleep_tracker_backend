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
        Schema::create('request_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->string('number', 32);
            $table->date('request_date');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
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
        Schema::dropIfExists('request_assets');
    }
};
