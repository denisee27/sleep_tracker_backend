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
        Schema::create('sap_adrc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('CLIENT')->nullable();
            $table->text('ADDRNUMBER')->nullable();
            $table->text('DATE_FROM')->nullable();
            $table->text('NATION')->nullable();
            $table->text('DATE_TO')->nullable();
            $table->text('TITLE')->nullable();
            $table->text('NAME1')->nullable();
            $table->text('NAME2')->nullable();
            $table->text('NAME3')->nullable();
            $table->text('NAME4')->nullable();
            $table->text('NAME_TEXT')->nullable();
            $table->text('NAME_CO')->nullable();
            $table->text('CITY1')->nullable();
            $table->text('CITY2')->nullable();
            $table->text('CITY_CODE')->nullable();
            $table->text('CITYP_CODE')->nullable();
            $table->text('HOME_CITY')->nullable();
            $table->text('CITYH_CODE')->nullable();
            $table->text('CHCKSTATUS')->nullable();
            $table->text('REGIOGROUP')->nullable();
            $table->text('POST_CODE1')->nullable();
            $table->text('POST_CODE2')->nullable();
            $table->text('POST_CODE3')->nullable();
            $table->text('PCODE1_EXT')->nullable();
            $table->text('PCODE2_EXT')->nullable();
            $table->text('PCODE3_EXT')->nullable();
            $table->text('PO_BOX')->nullable();
            $table->text('DONT_USE_P')->nullable();
            $table->text('PO_BOX_NUM')->nullable();
            $table->text('PO_BOX_LOC')->nullable();
            $table->text('CITY_CODE2')->nullable();
            $table->text('PO_BOX_REG')->nullable();
            $table->text('PO_BOX_CTY')->nullable();
            $table->text('POSTALAREA')->nullable();
            $table->text('TRANSPZONE')->nullable();
            $table->text('STREET')->nullable();
            $table->text('DONT_USE_S')->nullable();
            $table->text('STREETCODE')->nullable();
            $table->text('STREETABBR')->nullable();
            $table->text('HOUSE_NUM1')->nullable();
            $table->text('HOUSE_NUM2')->nullable();
            $table->text('HOUSE_NUM3')->nullable();
            $table->text('STR_SUPPL1')->nullable();
            $table->text('STR_SUPPL2')->nullable();
            $table->text('STR_SUPPL3')->nullable();
            $table->text('LOCATION')->nullable();
            $table->text('BUILDING')->nullable();
            $table->text('FLOOR')->nullable();
            $table->text('ROOMNUMBER')->nullable();
            $table->text('COUNTRY')->nullable();
            $table->text('LANGU')->nullable();
            $table->text('REGION')->nullable();
            $table->text('ADDR_GROUP')->nullable();
            $table->text('FLAGGROUPS')->nullable();
            $table->text('PERS_ADDR')->nullable();
            $table->text('SORT1')->nullable();
            $table->text('SORT2')->nullable();
            $table->text('SORT_PHN')->nullable();
            $table->text('DEFLT_COMM')->nullable();
            $table->text('TEL_NUMBER')->nullable();
            $table->text('TEL_EXTENS')->nullable();
            $table->text('FAX_NUMBER')->nullable();
            $table->text('FAX_EXTENS')->nullable();
            $table->text('FLAGCOMM2')->nullable();
            $table->text('FLAGCOMM3')->nullable();
            $table->text('FLAGCOMM4')->nullable();
            $table->text('FLAGCOMM5')->nullable();
            $table->text('FLAGCOMM6')->nullable();
            $table->text('FLAGCOMM7')->nullable();
            $table->text('FLAGCOMM8')->nullable();
            $table->text('FLAGCOMM9')->nullable();
            $table->text('FLAGCOMM10')->nullable();
            $table->text('FLAGCOMM11')->nullable();
            $table->text('FLAGCOMM12')->nullable();
            $table->text('FLAGCOMM13')->nullable();
            $table->text('ADDRORIGIN')->nullable();
            $table->text('MC_NAME1')->nullable();
            $table->text('MC_CITY1')->nullable();
            $table->text('MC_STREET')->nullable();
            $table->text('EXTENSION1')->nullable();
            $table->text('EXTENSION2')->nullable();
            $table->text('TIME_ZONE')->nullable();
            $table->text('TAXJURCODE')->nullable();
            $table->text('ADDRESS_ID')->nullable();
            $table->text('LANGU_CREA')->nullable();
            $table->text('ADRC_UUID')->nullable();
            $table->text('UUID_BELATED')->nullable();
            $table->text('ID_CATEGORY')->nullable();
            $table->text('ADRC_ERR_STATUS')->nullable();
            $table->text('PO_BOX_LOBBY')->nullable();
            $table->text('DELI_SERV_TYPE')->nullable();
            $table->text('DELI_SERV_NUMBER')->nullable();
            $table->text('COUNTY_CODE')->nullable();
            $table->text('COUNTY')->nullable();
            $table->text('TOWNSHIP_CODE')->nullable();
            $table->text('TOWNSHIP')->nullable();
            $table->text('MC_COUNTY')->nullable();
            $table->text('MC_TOWNSHIP')->nullable();
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
        Schema::dropIfExists('sap_adrc');
    }
};
