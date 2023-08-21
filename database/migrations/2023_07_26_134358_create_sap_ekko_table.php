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
        Schema::create('sap_ekko', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('MANDT')->nullable();
            $table->text('EBELN')->nullable();
            $table->text('BUKRS')->nullable();
            $table->text('BSTYP')->nullable();
            $table->text('BSART')->nullable();
            $table->text('BSAKZ')->nullable();
            $table->text('LOEKZ')->nullable();
            $table->text('STATU')->nullable();
            $table->text('AEDAT')->nullable();
            $table->text('ERNAM')->nullable();
            $table->text('PINCR')->nullable();
            $table->text('LPONR')->nullable();
            $table->text('LIFNR')->nullable();
            $table->text('SPRAS')->nullable();
            $table->text('ZTERM')->nullable();
            $table->text('ZBD1T')->nullable();
            $table->text('ZBD2T')->nullable();
            $table->text('ZBD3T')->nullable();
            $table->text('ZBD1P')->nullable();
            $table->text('ZBD2P')->nullable();
            $table->text('EKORG')->nullable();
            $table->text('EKGRP')->nullable();
            $table->text('WAERS')->nullable();
            $table->text('WKURS')->nullable();
            $table->text('KUFIX')->nullable();
            $table->text('BEDAT')->nullable();
            $table->text('KDATB')->nullable();
            $table->text('KDATE')->nullable();
            $table->text('BWBDT')->nullable();
            $table->text('ANGDT')->nullable();
            $table->text('BNDDT')->nullable();
            $table->text('GWLDT')->nullable();
            $table->text('AUSNR')->nullable();
            $table->text('ANGNR')->nullable();
            $table->text('IHRAN')->nullable();
            $table->text('IHREZ')->nullable();
            $table->text('VERKF')->nullable();
            $table->text('TELF1')->nullable();
            $table->text('LLIEF')->nullable();
            $table->text('KUNNR')->nullable();
            $table->text('KONNR')->nullable();
            $table->text('ABGRU')->nullable();
            $table->text('AUTLF')->nullable();
            $table->text('WEAKT')->nullable();
            $table->text('RESWK')->nullable();
            $table->text('LBLIF')->nullable();
            $table->text('INCO1')->nullable();
            $table->text('INCO2')->nullable();
            $table->text('KTWRT')->nullable();
            $table->text('SUBMI')->nullable();
            $table->text('KNUMV')->nullable();
            $table->text('KALSM')->nullable();
            $table->text('STAFO')->nullable();
            $table->text('LIFRE')->nullable();
            $table->text('EXNUM')->nullable();
            $table->text('UNSEZ')->nullable();
            $table->text('LOGSY')->nullable();
            $table->text('UPINC')->nullable();
            $table->text('STAKO')->nullable();
            $table->text('FRGGR')->nullable();
            $table->text('FRGSX')->nullable();
            $table->text('FRGKE')->nullable();
            $table->text('FRGZU')->nullable();
            $table->text('FRGRL')->nullable();
            $table->text('LANDS')->nullable();
            $table->text('LPHIS')->nullable();
            $table->text('ADRNR')->nullable();
            $table->text('STCEG_L')->nullable();
            $table->text('STCEG')->nullable();
            $table->text('ABSGR')->nullable();
            $table->text('ADDNR')->nullable();
            $table->text('KORNR')->nullable();
            $table->text('MEMORY')->nullable();
            $table->text('PROCSTAT')->nullable();
            $table->text('RLWRT')->nullable();
            $table->text('REVNO')->nullable();
            $table->text('SCMPROC')->nullable();
            $table->text('REASON_CODE')->nullable();
            $table->text('MEMORYTYPE')->nullable();
            $table->text('RETTP')->nullable();
            $table->text('RETPC')->nullable();
            $table->text('DPTYP')->nullable();
            $table->text('DPPCT')->nullable();
            $table->text('DPAMT')->nullable();
            $table->text('DPDAT')->nullable();
            $table->text('MSR_ID')->nullable();
            $table->text('HIERARCHY_EXISTS')->nullable();
            $table->text('THRESHOLD_EXISTS')->nullable();
            $table->text('LEGAL_CONTRACT')->nullable();
            $table->text('DESCRIPTION')->nullable();
            $table->text('RELEASE_DATE')->nullable();
            $table->text('VSART')->nullable();
            $table->text('HANDOVERLOC')->nullable();
            $table->text('FORCE_ID')->nullable();
            $table->text('FORCE_CNT')->nullable();
            $table->text('RELOC_ID')->nullable();
            $table->text('RELOC_SEQ_ID')->nullable();
            $table->text('POHF_TYPE')->nullable();
            $table->text('EQ_EINDT')->nullable();
            $table->text('EQ_WERKS')->nullable();
            $table->text('FIXPO')->nullable();
            $table->text('EKGRP_ALLOW')->nullable();
            $table->text('WERKS_ALLOW')->nullable();
            $table->text('CONTRACT_ALLOW')->nullable();
            $table->text('PSTYP_ALLOW')->nullable();
            $table->text('FIXPO_ALLOW')->nullable();
            $table->text('KEY_ID_ALLOW')->nullable();
            $table->text('AUREL_ALLOW')->nullable();
            $table->text('DELPER_ALLOW')->nullable();
            $table->text('EINDT_ALLOW')->nullable();
            $table->text('OTB_LEVEL')->nullable();
            $table->text('OTB_COND_TYPE')->nullable();
            $table->text('KEY_ID')->nullable();
            $table->text('OTB_VALUE')->nullable();
            $table->text('OTB_CURR')->nullable();
            $table->text('OTB_RES_VALUE')->nullable();
            $table->text('OTB_SPEC_VALUE')->nullable();
            $table->text('SPR_RSN_PROFILE')->nullable();
            $table->text('BUDG_TYPE')->nullable();
            $table->text('OTB_STATUS')->nullable();
            $table->text('OTB_REASON')->nullable();
            $table->text('CHECK_TYPE')->nullable();
            $table->text('CON_OTB_REQ')->nullable();
            $table->text('CON_PREBOOK_LEV')->nullable();
            $table->text('CON_DISTR_LEV')->nullable();
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
        Schema::dropIfExists('sap_ekko');
    }
};