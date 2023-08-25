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
        Schema::table('assets', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('sub_category_id');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->longText('image')->nullable()->after('description');
            $table->string('serial_number', 64)->nullable()->after('name')->unique();
            $table->string('asset_number', 64)->nullable()->after('name')->unique();
            $table->string('io_number', 64)->nullable('after_name');
            $table->dropColumn('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('serial_number');
            $table->dropColumn('io_number');
            $table->dropColumn('asset_number');
            $table->dropColumn('image');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->string('code', 64)->unique()->after('sub_category_id');
        });
    }
};
