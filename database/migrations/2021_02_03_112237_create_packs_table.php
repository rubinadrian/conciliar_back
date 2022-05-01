<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('NROPERSONA')->nullable();
            $table->string('POLIZA',30)->nullable();
            $table->double('IMP_PACK',15,2)->nullable();
            $table->unsignedInteger('CUOTAS')->default('1');
            $table->unique(['NROPERSONA', 'POLIZA']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('packs');
    }
}
