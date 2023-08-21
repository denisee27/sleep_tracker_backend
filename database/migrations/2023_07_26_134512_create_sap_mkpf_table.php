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
        Schema::create('sap_mkpf', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('MANDT')->nullable();
            $table->text('MBLNR')->nullable();
            $table->text('MJAHR')->nullable();
            $table->text('VGART')->nullable();
            $table->text('BLART')->nullable();
            $table->text('BLAUM')->nullable();
            $table->text('BLDAT')->nullable();
            $table->text('BUDAT')->nullable();
            $table->text('CPUDT')->nullable();
            $table->text('CPUTM')->nullable();
            $table->text('AEDAT')->nullable();
            $table->text('USNAM')->nullable();
            $table->text('TCODE')->nullable();
            $table->text('XBLNR')->nullable();
            $table->text('BKTXT')->nullable();
            $table->text('FRATH')->nullable();
            $table->text('FRBNR')->nullable();
            $table->text('WEVER')->nullable();
            $table->text('XABLN')->nullable();
            $table->text('AWSYS')->nullable();
            $table->text('BLA2D')->nullable();
            $table->text('TCODE2')->nullable();
            $table->text('BFWMS')->nullable();
            $table->text('EXNUM')->nullable();
            $table->text('SPE_BUDAT_UHR')->nullable();
            $table->text('SPE_BUDAT_ZONE')->nullable();
            $table->text('LE_VBELN')->nullable();
            $table->text('SPE_LOGSYS')->nullable();
            $table->text('SPE_MDNUM_EWM')->nullable();
            $table->text('GTS_CUSREF_NO')->nullable();
            $table->text('FLS_RSTO')->nullable();
            $table->text('MSR_ACTIVE')->nullable();
            $table->text('KNUMV')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sap_mkpf');
    }
};
