<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCuotasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cuotas', function (Blueprint $table) {
            $table->string('SISTEMA','2')->default('VT'); //VT VENTAS PC PLANILLA CAJA
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cuotas', function (Blueprint $table) {
            $table->dropColumn('SISTEMA');
        });
    }
}
