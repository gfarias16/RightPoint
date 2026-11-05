<?php

namespace App\Services;

use App\Models\Municipio;
use App\Support\IbgeHttp;
use DomainException;

class ConsultaContextoIbge
{
    public const FAIXAS = [
        'populacao_0_14' => [93070, 93084, 93085],
        'populacao_15_64' => [93086, 93087, 93088, 93089, 93090, 93091, 93092, 93093, 93094, 93095],
        'populacao_65_mais' => [93096, 93097, 93098, 49108, 49109, 60040, 60041, 6653],
    ];

    public function consultar(string $tipo, string $ano): array
    {
        if (! in_array($tipo, ['estimativa', 'idades'], true) || ! preg_match('/^20[0-9]{2}$/D', $ano) || ($tipo === 'idades' && $ano !== '2022')) {
            throw new DomainException('Use estimativa/ano publicado ou idades/2022.');
        }
        $tabela = $tipo === 'estimativa' ? 6579 : 9514;
        $variavel = $tipo === 'estimativa' ? 9324 : 93;
        $base = 'https://servicodados.ibge.gov.br/api/v3/agregados/'.$tabela;
        $meta = IbgeHttp::request()->connectTimeout(10)->timeout(35)->get($base.'/metadados');
        if (! $meta->successful()) {
            throw new DomainException('Metadados do IBGE indisponíveis.');
        }
        $m = $meta->json();
        $v = collect($m['variaveis'] ?? [])->firstWhere('id', $variavel);
        if (($m['id'] ?? null) !== $tabela || ($v['unidade'] ?? null) !== 'Pessoas'
            || ! in_array('N6', $m['nivelTerritorial']['Administrativo'] ?? [], true)
            || (int) ($m['periodicidade']['inicio'] ?? 9999) > (int) $ano || (int) ($m['periodicidade']['fim'] ?? 0) < (int) $ano) {
            throw new DomainException('Tabela, ano, unidade ou nível municipal divergentes.');
        }
        $query = ['localidades' => 'N6[N3[33]]'];
        if ($tipo === 'idades') {
            $idade = collect($m['classificacoes'] ?? [])->firstWhere('id', 287);
            $ids = array_merge([100362], ...array_values(self::FAIXAS));
            $categorias = collect($idade['categorias'] ?? [])->keyBy('id');
            $nomes = ['Total'];
            for ($i = 0; $i < 100; $i += 5) {
                $nomes[] = $i.' a '.($i + 4).' anos';
            }
            $nomes[] = '100 anos ou mais';
            foreach ($ids as $i => $id) {
                if (($categorias[$id]['nome'] ?? null) !== $nomes[$i]) {
                    throw new DomainException('Faixas etárias não correspondem às categorias não sobrepostas conferidas.');
                }
            }
            $query['classificacao'] = '2[6794]|286[113635]|287['.implode(',', $ids).']';
        }
        $resposta = IbgeHttp::request()->connectTimeout(10)->timeout(60)->get($base.'/periodos/'.$ano.'/variaveis/'.$variavel, $query);
        if (! $resposta->successful()) {
            throw new DomainException('Valores do contexto IBGE indisponíveis.');
        }
        $municipios = Municipio::where('uf', 'RJ')->pluck('id', 'codigo_ibge')->all();
        $linhas = $this->validar($resposta->json(), $tipo, $ano, $municipios);

        return ['linhas' => $linhas, 'fonte' => $tipo === 'estimativa' ? 'ibge-estimativas-populacao' : 'ibge-censo-2022-idades',
            'nome' => $tipo === 'estimativa' ? 'IBGE - Estimativas de população' : 'IBGE - Censo 2022 - Idades',
            'url' => $base.'/periodos/'.$ano.'/variaveis/'.$variavel.'?'.http_build_query($query),
            'versao' => 'Tabela '.$tabela.' / variável '.$variavel.' / recorte RJ v1', 'ano' => $ano];
    }

    public function validar(mixed $dados, string $tipo, string $ano, array $municipios): array
    {
        $esperados = array_values(config('municipios_receita'));
        if (count($municipios) !== 92 || array_diff(array_keys($municipios), $esperados) || ! is_array($dados) || count($dados) !== 1
            || ($dados[0]['id'] ?? null) !== ($tipo === 'estimativa' ? '9324' : '93') || ($dados[0]['unidade'] ?? null) !== 'Pessoas') {
            throw new DomainException('Catálogo ou variável de contexto divergentes.');
        }
        $resultados = $dados[0]['resultados'] ?? [];
        $ids = $tipo === 'estimativa' ? ['total'] : array_map('strval', array_merge([100362], ...array_values(self::FAIXAS)));
        if (count($resultados) !== count($ids)) {
            throw new DomainException('Conjunto de categorias incompleto.');
        }
        $valores = [];
        foreach ($resultados as $resultado) {
            $categoria = 'total';
            if ($tipo === 'idades') {
                $classes = collect($resultado['classificacoes'] ?? []);
                if ($classes->count() !== 3 || array_keys($classes->firstWhere('id', '2')['categoria'] ?? []) !== [6794]
                    || array_keys($classes->firstWhere('id', '286')['categoria'] ?? []) !== [113635]) {
                    throw new DomainException('Esperados totais de sexo e declaração da idade.');
                }
                $categorias = $classes->firstWhere('id', '287')['categoria'] ?? [];
                if (count($categorias) !== 1) {
                    throw new DomainException('Categoria etária ambígua.');
                }
                $categoria = (string) array_key_first($categorias);
            } elseif (! empty($resultado['classificacoes'])) {
                throw new DomainException('Estimativa não pode conter recortes extras.');
            }
            if (! in_array($categoria, $ids, true) || isset($valores[$categoria]) || count($resultado['series'] ?? []) !== 92) {
                throw new DomainException('Categorias/séries incompletas ou duplicadas.');
            }
            $valores[$categoria] = [];
            foreach ($resultado['series'] as $serie) {
                $codigo = $serie['localidade']['id'] ?? '';
                $v = $serie['serie'][$ano] ?? null;
                // Na convenção do IBGE, "-" representa zero absoluto; outros símbolos continuam inválidos.
                if ($tipo === 'idades' && $v === '-') {
                    $v = '0';
                }
                if (! is_string($codigo) || ! isset($municipios[$codigo]) || isset($valores[$categoria][$codigo])
                    || ($serie['localidade']['nivel']['id'] ?? null) !== 'N6' || count($serie['serie'] ?? []) !== 1
                    || ! is_string($v) || ! preg_match('/^(0|[1-9][0-9]{0,12})$/D', $v) || (($tipo === 'estimativa' || $categoria === '100362') && (int) $v === 0)) {
                    throw new DomainException('Município, período ou população inválida/duplicada.');
                }
                $valores[$categoria][$codigo] = (int) $v;
            }
        }
        $linhas = [];
        foreach ($municipios as $codigo => $id) {
            if ($tipo === 'estimativa') {
                $linhas[] = ['municipio_id' => $id, 'codigo_indicador' => 'populacao_estimada', 'valor' => $valores['total'][$codigo]];
            } else {
                $soma = 0;
                foreach (self::FAIXAS as $indicador => $categorias) {
                    $n = array_sum(array_map(fn ($categoria) => $valores[$categoria][$codigo], $categorias));
                    $soma += $n;
                    $linhas[] = ['municipio_id' => $id, 'codigo_indicador' => $indicador, 'valor' => $n];
                }
                if ($soma !== $valores['100362'][$codigo]) {
                    throw new DomainException('Faixas etárias não somam o total municipal.');
                }
            }
        }

        return $linhas;
    }
}
