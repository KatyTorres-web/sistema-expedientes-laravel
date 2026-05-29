<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('historial_cambios', function (Blueprint $table) {
            $table->id('id_historial');
            $table->string('accion', 250);
            $table->text('valor_anterior')->nullable();
            $table->dateTime('fecha_cambio')->useCurrent();
            $table->text('valor_nuevo')->nullable();
            $table->string('campo_modificado', 250);

            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_expediente');

            $table->foreign('id_usuario')->references('id')->on('users');
            $table->foreign('id_expediente')->references('id_expediente')->on('expedientes');

            $table->index('id_usuario');
            $table->index('id_expediente');
            $table->index('fecha_cambio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_cambios');
    }
};
