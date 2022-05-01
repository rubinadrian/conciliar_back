<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationToPagos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->biginteger('grupo_pago_id')->unsigned()->nullable();
            $table->foreign('grupo_pago_id')->references('id')->on('grupo_pagos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign('pagos_grupo_pago_id_foreign');
            $table->dropColumn(['grupo_pago_id']);
        });
    }
}
