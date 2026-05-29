<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // id estándar

            $table->string('apellido', 100);
            $table->string('nombre', 100);
            $table->string('celular', 15)->nullable();

            $table->string('email', 100)->unique(); 
            $table->string('username', 50)->unique();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password'); 
            
            $table->unsignedBigInteger('estado_usuario_id');
            $table->unsignedBigInteger('rol_id');
            $table->unsignedBigInteger('subdivision_id');

            $table->rememberToken();
            $table->timestamps();

            $table->foreign('estado_usuario_id')->references('id_estado_usuario')->on('estado_usuarios');
            $table->foreign('rol_id')->references('id_rol')->on('roles');
            $table->foreign('subdivision_id')->references('id_subdivision')->on('subdivision_areas');
            $table->timestamp('ultimo_login')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
