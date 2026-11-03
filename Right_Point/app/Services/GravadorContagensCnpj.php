<?php

namespace App\Services;

use App\Models\ContagemEstabelecimento;
use App\Models\FonteDado;
use DomainException;
use Illuminate\Support\Facades\DB;

class GravadorContagensCnpj
{
    /**
     * Recebe somente linhas já validadas e metadados de uma fotografia conferida.
     * A simulação dos comandos não chama este serviço.
     *
     * @return array{criadas: int, existentes: int}
     */
    public function gravar(array $linhas, string $consultadoEm, ?string $periodo = null, ?string $versao = null): array
    {
        return DB::transaction(function () use ($linhas, $consultadoEm, $periodo, $versao): array {
            $fonte = FonteDado::firstOrCreate(
                ['codigo' => 'opencnpj-receita'],
                ['nome' => 'OpenCNPJ - dados da Receita Federal', 'url_referencia' => 'https://opencnpj.org/']
            );

            // Compartilha o bloqueio com o importador singular do piloto antigo.
            $fonte = FonteDado::query()->whereKey($fonte->id)->lockForUpdate()->firstOrFail();
            $chaveComum = [
                'fonte_dado_id' => $fonte->id,
                'criterio' => 'ativos_cnae_principal_exato_v1',
                'consultado_em' => $consultadoEm,
            ];
            $chave = fn ($linha) => $linha['municipio_id'].':'.$linha['atividade_economica_id'];
            $chavesEsperadas = array_map($chave, $linhas);
            // Usa chaves município/atividade; only() no Eloquent usaria os IDs das coletas.
            $existentes = ContagemEstabelecimento::query()
                ->where($chaveComum)
                ->whereIn('municipio_id', array_column($linhas, 'municipio_id'))
                ->whereIn('atividade_economica_id', array_column($linhas, 'atividade_economica_id'))
                ->lockForUpdate()
                ->get()
                ->toBase()
                ->keyBy(fn ($coleta) => $coleta->municipio_id.':'.$coleta->atividade_economica_id)
                ->only($chavesEsperadas);

            // Verifica todo o conjunto antes da primeira inserção.
            foreach ($linhas as $linha) {
                $existente = $existentes->get($chave($linha));

                if ($existente === null) {
                    continue;
                }

                foreach (['quantidade', 'registros_no_recorte', 'cnpjs_ausentes'] as $campo) {
                    if ($existente->{$campo} !== $linha[$campo]) {
                        throw new DomainException('Coleta divergente para IBGE '.$linha['codigo_ibge'].'. Nenhum valor foi sobrescrito; transação desfeita.');
                    }
                }
            }

            $criadas = 0;

            foreach ($linhas as $linha) {
                if ($existentes->has($chave($linha))) {
                    continue;
                }

                ContagemEstabelecimento::create($chaveComum + [
                    'municipio_id' => $linha['municipio_id'],
                    'atividade_economica_id' => $linha['atividade_economica_id'],
                    'quantidade' => $linha['quantidade'],
                    'registros_no_recorte' => $linha['registros_no_recorte'],
                    'cnpjs_ausentes' => $linha['cnpjs_ausentes'],
                    'periodo_referencia' => $periodo,
                    'versao_fonte' => $versao,
                ]);
                $criadas++;
            }

            return ['criadas' => $criadas, 'existentes' => $existentes->count()];
        }, 3);
    }
}
