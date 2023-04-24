<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leadword', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('nacionalidade');
            $table->string('profissao');
            $table->string('civil');
            $table->string('rg');
            $table->string('cpf');
            $table->string('rua');
            $table->string('numero');
            $table->string('bairro');
            $table->string('estado');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leadword');
    }
};
