<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contagens_estabelecimentos', function (Blueprint $table) {
            $table->id();

            // Preserva as referências enquanto existirem contagens associadas.
            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->restrictOnDelete();

            $table->foreignId('atividade_economica_id')
                ->constrained('atividades_economicas')
                ->restrictOnDelete();

            $table->foreignId('fonte_dado_id')
                ->constrained('fontes_dados')
                ->restrictOnDelete();

            $table->unsignedInteger('quantidade');
            $table->unsignedInteger('registros_no_recorte');
            $table->unsignedInteger('cnpjs_ausentes');

            // Identifica a regra usada, permitindo versões futuras.
            $table->string('criterio', 60);

            // Referência da base não é a data em que executamos a consulta.
            $table->string('periodo_referencia', 20)->nullable();
            $table->string('versao_fonte', 100)->nullable();
            $table->dateTime('consultado_em');

            $table->timestamps();

            // Impede duplicar a mesma coleta; consultas novas ficam separadas.
            $table->unique(
                [
                    'municipio_id',
                    'atividade_economica_id',
                    'fonte_dado_id',
                    'criterio',
                    'consultado_em',
                ],
                'contagens_estabelecimentos_coleta_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contagens_estabelecimentos');
    }
};