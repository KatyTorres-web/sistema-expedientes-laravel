<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes', function (Blueprint $table) {
            $table->id('id_expediente');

            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_estado_expediente');
            $table->unsignedBigInteger('area_actual');
            $table->unsignedInteger('correlativo_anual');
            $table->string('num_expediente', 100);
            $table->year('anio_expediente');
            $table->dateTime('fecha_registro')->useCurrent();
            $table->text('descripcion');
            $table->unsignedInteger('folios_actual')->default(0);
            $table->longText('observaciones')->nullable();
            $table->string('razon_social', 250)->nullable();
            $table->string('apellidos_y_nombres', 250)->nullable();
            $table->date('fecha_recepcion');
            $table->enum('tipo_presentante', ['NATURAL', 'JURIDICA']);
            $table->text('documentos_adjuntos')->nullable();

            $table->foreign('id_usuario')->references('id')->on('users');
            $table->foreign('id_estado_expediente')->references('id_estado_expediente')->on('estado_expedientes');
            $table->foreign('area_actual') ->references('id_subdivision') ->on('subdivision_areas');

            $table->index('anio_expediente');
            $table->index('id_estado_expediente');
            $table->index('id_usuario');
            $table->index('area_actual');

            $table->unique(['correlativo_anual', 'anio_expediente']);
            $table->unique(['num_expediente', 'anio_expediente']);
        });

    }

    
    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};
