<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransaccionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['recarga', 'pago'])->default('pago');
            $table->decimal('valor', 19, 2)->default(0.00);
            $table->enum('estado', ['fallido', 'procesando','ejecutado'])->default('procesando');
            $table->text('token_confirmacion')->nullable()->default(null);
            $table->text('token_usuario')->nullable()->default(null);
            $table->integer('user_executer_id')->unsigned();
            $table->integer('user_receptor_id')->unsigned()->nullable()->default(null);
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
        Schema::dropIfExists('transacciones');
    }
}
