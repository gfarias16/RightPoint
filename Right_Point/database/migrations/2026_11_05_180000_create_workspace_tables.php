<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfis_negocio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nome', 80);
            $table->string('codigo_ibge', 7);
            $table->string('cnae', 7);
            $table->string('notas', 240)->nullable();
            $table->string('origem_local', 80)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'updated_at']);
            $table->unique(['user_id', 'origem_local']);
        });
        Schema::create('diagnosticos_salvos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titulo', 120);
            $table->string('codigo_ibge', 7);
            $table->string('cnae', 7);
            $table->string('metodo', 100);
            $table->char('sha256', 64);
            $table->json('snapshot');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosticos_salvos');
        Schema::dropIfExists('perfis_negocio');
    }
};
