<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterConciliacionesTipoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conciliaciones', function (Blueprint $table) {
            $table->unsignedInteger('tipo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('conciliaciones', 'tipo'))
        {
            Schema::table('conciliaciones', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }
    }
}
