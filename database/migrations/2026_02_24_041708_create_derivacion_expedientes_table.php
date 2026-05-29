<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('derivacion_expedientes', function (Blueprint $table) {
            $table->id('id_derivacion');
            $table->dateTime('fecha_derivacion')->useCurrent();
            $table->dateTime('fecha_atencion')->nullable();

            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_expediente');

            $table->foreign('id_usuario')->references('id')->on('users');
            $table->foreign('id_expediente')->references('id_expediente')->on('expedientes');

            $table->index('id_usuario');
            $table->index('id_expediente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derivacion_expedientes');
    }
};
