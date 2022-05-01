<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->string('NRO_VINCULANTE',50)->nullable();
            $table->string('EXPEDIENTE',50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packs', function (Blueprint $table) {
            $table->dropColumn('NRO_VINCULANTE');
            $table->dropColumn('EXPEDIENTE');
        });
    }
}
