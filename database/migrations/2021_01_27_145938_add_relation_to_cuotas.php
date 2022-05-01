<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationToCuotas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cuotas', function (Blueprint $table) {
            $table->biginteger('grupo_cuota_id')->unsigned()->nullable();
            $table->foreign('grupo_cuota_id')->references('id')->on('grupo_cuotas');
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
            $table->dropForeign('cuotas_grupo_cuota_id_foreign');
            $table->dropColumn(['grupo_cuota_id']);
        });
    }
}
