<?php

namespace App\Services;

use App\Models\AtividadeEconomica;
use App\Models\Municipio;
use DomainException;
use Illuminate\Support\Str;

class ValidadorContagensRj
{
    private const MAX_BYTES = 1048576;

    public const CNAES_PILOTO = ['5611201', '1091102', '4721102', '9602501'];

    /**
     * Retorna uma fotografia validada do arquivo e dos IDs locais.
     * O importador usa este conteúdo em memória, sem reabrir o arquivo.
     *
     * @return array{linhas: list<array>, atividade_id: int, total: int, sha256: string}
     */
    public function validar(string $caminho): array
    {
        $lote = $this->validarArquivo($caminho, ['5611201'], true);
        $lote['atividade_id'] = $lote['linhas'][0]['atividade_economica_id'];

        return $lote;
    }

    /**
     * A exportação agrupada pode omitir municípios sem registros.
     * Ausências permanecem indisponíveis; não fabricamos linhas com zero.
     */
    public function validarPiloto(string $caminho): array
    {
        return $this->validarArquivo($caminho, self::CNAES_PILOTO, false);
    }

    private function validarArquivo(string $caminho, array $cnaes, bool $exigirTodosMunicipios): array
    {
        if (! is_file($caminho) || ! is_readable($caminho)) {
            throw new DomainException('Arquivo do lote não encontrado ou sem permissão de leitura.');
        }

        $conteudo = file_get_contents($caminho, false, null, 0, self::MAX_BYTES + 1);

        if ($conteudo === false || strlen($conteudo) > self::MAX_BYTES) {
            throw new DomainException('Não foi possível ler o lote ou o arquivo ultrapassa 1 MiB.');
        }

        if (PHP_INT_SIZE < 8) {
            throw new DomainException('Esta validação exige PHP com inteiros de 64 bits.');
        }

        $registros = json_decode($conteudo, true, 512, JSON_THROW_ON_ERROR);
        $this->validarRegistros($registros, $cnaes, $exigirTodosMunicipios);
        $mapa = $this->mapaConferido();
        $chaves = array_map(fn ($item) => 'RJ:'.$item['codigo_municipio'], $registros);

        if (array_diff($chaves, array_keys($mapa))
            || ($exigirTodosMunicipios && array_diff(array_keys($mapa), $chaves))) {
            throw new DomainException($exigirTodosMunicipios
                ? 'O lote não corresponde aos 92 códigos Receita configurados.'
                : 'O lote contém código Receita fora das correspondências RJ configuradas.');
        }

        $municipios = Municipio::query()->where('uf', 'RJ')->get()->keyBy('codigo_ibge');
        $atividades = AtividadeEconomica::query()->whereIn('codigo_cnae', $cnaes)->get()->keyBy('codigo_cnae');

        foreach ($cnaes as $cnae) {
            if (! $atividades->has($cnae)) {
                throw new DomainException('CNAE '.$cnae.' não cadastrado neste banco.');
            }
        }

        $linhas = [];
        $total = 0;
        $resumo = [];

        foreach ($cnaes as $cnae) {
            $resumo[$cnae] = ['codigo_cnae' => $cnae, 'municipios_com_registro' => 0, 'total' => null];
        }

        foreach ($registros as $indice => $registro) {
            $codigoIbge = $mapa['RJ:'.$registro['codigo_municipio']];
            $municipio = $municipios->get($codigoIbge);

            if ($municipio === null) {
                throw new DomainException('Registro '.($indice + 1).': município IBGE não cadastrado no RJ.');
            }

            if (! $this->nomeConfere($registro, $codigoIbge, $municipio->nome)) {
                throw new DomainException('Registro '.($indice + 1).': nome municipal diverge da correspondência configurada.');
            }

            $quantidade = (int) $registro['cnpjs_distintos'];
            $cnae = $registro['cnae_principal'];
            $total += $quantidade;
            $resumo[$cnae]['municipios_com_registro']++;
            $resumo[$cnae]['total'] = ($resumo[$cnae]['total'] ?? 0) + $quantidade;
            $linhas[] = [
                'municipio_id' => $municipio->id,
                'atividade_economica_id' => $atividades->get($cnae)->id,
                'codigo_cnae' => $cnae,
                'codigo_receita' => $registro['codigo_municipio'],
                'codigo_ibge' => $codigoIbge,
                'nome' => $municipio->nome,
                'quantidade' => $quantidade,
                'registros_no_recorte' => (int) $registro['registros_no_recorte'],
                'cnpjs_ausentes' => (int) $registro['cnpjs_ausentes'],
            ];
        }

        return [
            'linhas' => $linhas,
            'total' => $total,
            'resumo_cnaes' => array_values($resumo),
            'sha256' => hash('sha256', str_replace(["\r\n", "\r"], "\n", $conteudo)),
        ];
    }

    private function validarRegistros(mixed $registros, array $cnaes, bool $exigirTodosMunicipios): void
    {
        $limite = count($cnaes) * 92;

        if (! is_array($registros) || ! array_is_list($registros) || $registros === []
            || count($registros) > $limite || ($exigirTodosMunicipios && count($registros) !== 92)) {
            throw new DomainException($exigirTodosMunicipios
                ? 'Esperada uma lista JSON com exatamente 92 registros municipais.'
                : 'Esperada uma lista JSON com 1 a 368 grupos de município e CNAE do piloto.');
        }

        $campos = ['uf', 'codigo_municipio', 'municipio', 'cnae_principal', 'situacao_cadastral',
            'registros_no_recorte', 'cnpjs_distintos', 'cnpjs_ausentes'];
        $vistos = [];

        foreach ($registros as $indice => $registro) {
            $prefixo = 'Registro '.($indice + 1).': ';

            if (! is_array($registro) || array_diff($campos, array_keys($registro)) || array_diff(array_keys($registro), $campos)) {
                throw new DomainException($prefixo.'campos diferentes do contrato do SQL.');
            }

            foreach ($campos as $campo) {
                if (! is_string($registro[$campo]) || trim($registro[$campo]) === '') {
                    throw new DomainException($prefixo.'campo '.$campo.' ausente ou não textual.');
                }
            }

            if ($registro['uf'] !== 'RJ' || ! in_array($registro['cnae_principal'], $cnaes, true) || $registro['situacao_cadastral'] !== 'Ativa') {
                throw new DomainException($prefixo.($exigirTodosMunicipios
                    ? 'filtros diferentes de RJ/5611201/Ativa.'
                    : 'filtros diferentes de RJ/CNAEs do piloto/Ativa.'));
            }

            if (preg_match('/^[0-9]{4}$/D', $registro['codigo_municipio']) !== 1) {
                throw new DomainException($prefixo.'código Receita precisa conter quatro dígitos textuais.');
            }

            if (preg_match('/[\x00-\x1F\x7F]/u', $registro['municipio']) === 1 || $this->normalizarNome($registro['municipio']) === '') {
                throw new DomainException($prefixo.'nome municipal inválido.');
            }

            foreach (['registros_no_recorte', 'cnpjs_distintos', 'cnpjs_ausentes'] as $campo) {
                if (preg_match('/^(0|[1-9][0-9]{0,9})$/D', $registro[$campo]) !== 1
                    || filter_var($registro[$campo], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 4294967295]]) === false) {
                    throw new DomainException($prefixo.'campo '.$campo.' fora do formato ou limite de contagem.');
                }
            }

            if ($registro['cnpjs_ausentes'] !== '0' || $registro['registros_no_recorte'] !== $registro['cnpjs_distintos']) {
                throw new DomainException($prefixo.'ausências, duplicatas ou contagens inconsistentes.');
            }

            $chave = $registro['cnae_principal'].':RJ:'.$registro['codigo_municipio'];

            if (isset($vistos[$chave])) {
                throw new DomainException($prefixo.($exigirTodosMunicipios
                    ? 'código Receita repetido no lote.'
                    : 'par município/CNAE repetido no lote.'));
            }

            $vistos[$chave] = true;
        }
    }

    private function mapaConferido(): array
    {
        $configuracao = config('municipios_receita');

        if (! is_array($configuracao)) {
            throw new DomainException('Configuração municipal inválida.');
        }

        $mapa = array_filter($configuracao, fn ($chave) => str_starts_with($chave, 'RJ:'), ARRAY_FILTER_USE_KEY);
        $vistos = [];

        if (count($mapa) !== 92) {
            throw new DomainException('São necessárias 92 correspondências RJ. Confira a configuração e seu cache local.');
        }

        foreach ($mapa as $chave => $codigoIbge) {
            if (preg_match('/^RJ:[0-9]{4}$/D', $chave) !== 1 || ! is_string($codigoIbge) || preg_match('/^33[0-9]{5}$/D', $codigoIbge) !== 1) {
                throw new DomainException('Correspondência municipal com código inválido.');
            }

            if (isset($vistos[$codigoIbge])) {
                throw new DomainException('Código IBGE repetido nas correspondências RJ.');
            }

            $vistos[$codigoIbge] = true;
        }

        return $mapa;
    }

    private function nomeConfere(array $registro, string $codigoIbge, string $nomeLocal): bool
    {
        $nomeReceita = $this->normalizarNome($registro['municipio']);
        $nomeIbge = $this->normalizarNome($nomeLocal);

        if ($nomeReceita === $nomeIbge) {
            return true;
        }

        // Exceção documentada; não permite aproximação genérica de nomes.
        return $registro['codigo_municipio'] === '5875'
            && $codigoIbge === '3303807'
            && $nomeReceita === 'PARATI'
            && $nomeIbge === 'PARATY';
    }

    private function normalizarNome(string $nome): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(Str::ascii($nome)));
    }
}
