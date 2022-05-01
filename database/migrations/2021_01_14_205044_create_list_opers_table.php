<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListOpersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('list_opers', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('Emp',100)->nullable();
        //     $table->string('Secc',100)->nullable();
        //     $table->string('Producto',100)->nullable();
        //     $table->string('Referencia',100)->nullable();
        //     $table->string('Supl/End',100)->nullable();
        //     $table->string('Poliza/Contrato',100)->nullable();
        //     $table->string('Propuesta',100)->nullable();
        //     $table->string('Nombre',100)->nullable();
        //     $table->string('Doc/CUIT',100)->nullable();
        //     $table->string('CdPer',100)->nullable();
        //     $table->string('Emision',100)->nullable();
        //     $table->string('InicioVig',100)->nullable();
        //     $table->string('FinVig',100)->nullable();
        //     $table->string('Prima',100)->nullable();
        //     $table->string('Premio',100)->nullable();
        //     $table->string('Comision',100)->nullable();
        //     $table->string('Cuotas',100)->nullable();
        //     $table->string('Mon',100)->nullable();
        //     $table->string('Suma_Asegurada',100)->nullable();
        //     $table->string('Secc_Hija',100)->nullable();
        //     $table->string('Poliza_Hija',100)->nullable();
        //     $table->string('Premio_Hija',100)->nullable();
        //     $table->string('Premio_Madre',100)->nullable();
        //     $table->string('Tipo_Suple',100)->nullable();
        //     $table->string('Cuotas_Pagas',100)->nullable();
        //     $table->string('Total_Cobrado',100)->nullable();
        //     $table->string('Estado_Poliza',20)->nullable();
        //     $table->unique(['Producto', 'Supl/End', 'Referencia', 'Poliza/Contrato']);
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('list_opers');
    }
}
