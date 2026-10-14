<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicadores_municipais', function (Blueprint $table) {
            $table->id();

            // Referencia os IDs internos das tabelas; o código IBGE permanece em municipios.
            // Impede apagar município ou fonte enquanto houver indicadores associados.
            $table->foreignId('municipio_id')->constrained('municipios')->restrictOnDelete();
            $table->foreignId('fonte_dado_id')->constrained('fontes_dados')->restrictOnDelete();

            // O valor pode ser inteiro (populacao) ou decimal (outros indicadores).
            // Regras como "população não negativa" serão validadas na importação.
            $table->string('codigo_indicador', 50);
            $table->decimal('valor', 18, 4);
            $table->string('unidade', 40);

            // Período do dado (2022) não é a data em que fizemos a coleta.
            $table->string('periodo_referencia', 20);
            $table->string('versao_fonte', 100)->nullable();
            $table->timestamp('coletado_em');
            $table->timestamps();

            // Uma reimportação do mesmo indicador e período deverá atualizar esta linha.
            // A chave única impede duplicatas; o comando de importação fará a atualização.
            $table->unique(
                ['municipio_id', 'fonte_dado_id', 'codigo_indicador', 'periodo_referencia'],
                'indicadores_municipais_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicadores_municipais');
    }
};
