<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('subdivision_areas', function (Blueprint $table) {
            $table->id('id_subdivision');
            $table->string('cod_subdivision', 20) -> unique();
            $table->string('nombre_subdivision', 100);
            $table->boolean('estado_subdiv_area')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subdivision_areas');
    }
};

