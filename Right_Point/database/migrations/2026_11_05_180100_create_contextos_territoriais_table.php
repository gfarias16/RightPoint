<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contextos_territoriais', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 40);
            $table->string('codigo_ibge', 7);
            $table->string('fonte_url', 500);
            $table->string('periodo_referencia', 20)->nullable();
            $table->string('versao_fonte', 120);
            $table->dateTime('coletado_em');
            $table->char('sha256', 64)->unique();
            $table->json('dados');
            $table->timestamps();
            $table->index(['codigo_ibge', 'tipo', 'coletado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contextos_territoriais');
    }
};
