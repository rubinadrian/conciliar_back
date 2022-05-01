<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCuotas2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cuotas', function (Blueprint $table) {
            $table->string('NRO_VINCULANTE',50)->nullable();
            $table->string('EXPEDIENTE',50)->nullable();
            $table->string('CUENTA',50)->default('13017');
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
            $table->dropColumn('NRO_VINCULANTE');
            $table->dropColumn('EXPEDIENTE');
            $table->dropColumn('CUENTA');
        });
    }
}
