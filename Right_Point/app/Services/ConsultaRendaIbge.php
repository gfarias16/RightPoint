<?php

namespace App\Services;

use App\Models\Municipio;
use App\Support\IbgeHttp;
use DomainException;

class ConsultaRendaIbge
{
    public const URL_BASE = 'https://servicodados.ibge.gov.br/api/v3/agregados/10295';

    public const CODIGO_FONTE = 'ibge-censo-2022-renda-domiciliar';

    public const CODIGO_INDICADOR = 'rendimento_domiciliar_per_capita_medio';

    public const VERSAO_RECORTE = 'Censo 2022 - tabela 10295 - variavel 13431 - totais';

    public const NOME_VARIAVEL = 'Valor do rendimento nominal médio mensal domiciliar per capita dos moradores em domicílios particulares permanentes ocupados, exclusive os cuja condição no domicílio era pensionista, empregado(a) doméstico(a) ou parente do(a) empregado(a) doméstico(a)';

    public const FILTROS = [
        'localidades' => 'N6[N3[33]]',
        'classificacao' => '2[6794]|86[95251]|58[95253]',
    ];

    public function consultar(): array
    {
        $metadados = IbgeHttp::request()->connectTimeout(10)->timeout(45)->get(self::URL_BASE.'/metadados');

        if (! $metadados->successful()) {
            throw new DomainException('Não foi possível conferir os metadados da renda no IBGE (HTTP '.$metadados->status().').');
        }

        $this->validarMetadados($metadados->json());
        $resposta = IbgeHttp::request()->connectTimeout(10)->timeout(45)->get(self::URL_BASE.'/periodos/2022/variaveis/13431', self::FILTROS);

        if (! $resposta->successful()) {
            throw new DomainException('Não foi possível consultar os valores da renda no IBGE (HTTP '.$resposta->status().').');
        }

        $municipios = Municipio::where('uf', 'RJ')->pluck('id', 'codigo_ibge')->all();

        return $this->validarValores($resposta->json(), $municipios);
    }

    public function validarMetadados(mixed $dados): void
    {
        if (! is_array($dados) || ($dados['id'] ?? null) !== 10295
            || ($dados['pesquisa'] ?? null) !== 'Censo Demográfico'
            || ($dados['periodicidade']['inicio'] ?? null) !== 2022
            || ($dados['periodicidade']['fim'] ?? null) !== 2022
            || ! in_array('N6', $dados['nivelTerritorial']['Administrativo'] ?? [], true)) {
            throw new DomainException('Tabela, pesquisa, período ou nível municipal da renda não conferem.');
        }

        $variaveis = $dados['variaveis'] ?? [];
        $variavel = is_array($variaveis) ? collect($variaveis)->first(fn ($v) => is_array($v) && ($v['id'] ?? null) === 13431) : null;

        if (($variavel['nome'] ?? null) !== self::NOME_VARIAVEL || ($variavel['unidade'] ?? null) !== 'Reais') {
            throw new DomainException('Variável de rendimento médio nominal ou unidade não conferem.');
        }
    }

    /**
     * Valida o lote inteiro antes de qualquer escrita. Os IDs vêm do catálogo local.
     * Valores são decimais textuais: não converter símbolos de ausência em zero.
     *
     * @param  array<string, int>  $municipios
     * @return list<array{municipio_id: int, codigo_ibge: string, valor: string}>
     */
    public function validarValores(mixed $dados, array $municipios): array
    {
        if (count($municipios) !== 92 || ! is_array($dados) || ! array_is_list($dados) || count($dados) !== 1) {
            throw new DomainException('Esperados catálogo com 92 municípios e uma única variável de renda.');
        }

        $variavel = $dados[0];

        if (($variavel['id'] ?? null) !== '13431' || ($variavel['variavel'] ?? null) !== self::NOME_VARIAVEL
            || ($variavel['unidade'] ?? null) !== 'Reais'
            || ! is_array($variavel['resultados'] ?? null) || ! array_is_list($variavel['resultados']) || count($variavel['resultados']) !== 1) {
            throw new DomainException('Resposta não corresponde à renda média nominal e ao recorte único esperado.');
        }

        $resultado = $variavel['resultados'][0];
        $classificacoes = $resultado['classificacoes'] ?? null;
        $esperadas = ['2' => '6794', '86' => '95251', '58' => '95253'];

        if (! is_array($classificacoes) || ! array_is_list($classificacoes) || count($classificacoes) !== 3) {
            throw new DomainException('Classificações totais de sexo, cor/raça e idade não conferem.');
        }

        foreach ($classificacoes as $classificacao) {
            $id = $classificacao['id'] ?? null;
            $categoria = $classificacao['categoria'] ?? null;

            if (! is_string($id) || ! isset($esperadas[$id]) || ! is_array($categoria)
                || count($categoria) !== 1 || ($categoria[$esperadas[$id]] ?? null) !== 'Total') {
                throw new DomainException('Recorte de renda diferente dos totais conferidos.');
            }

            unset($esperadas[$id]);
        }

        $series = $resultado['series'] ?? null;

        if (! is_array($series) || ! array_is_list($series) || count($series) !== 92) {
            throw new DomainException('A resposta de renda não contém as 92 séries municipais.');
        }

        $linhas = [];
        $vistos = [];

        foreach ($series as $item) {
            $codigo = $item['localidade']['id'] ?? null;
            $serie = $item['serie'] ?? null;
            $valor = is_array($serie) ? ($serie['2022'] ?? null) : null;

            if (! is_string($codigo) || preg_match('/^33[0-9]{5}$/D', $codigo) !== 1
                || ($item['localidade']['nivel']['id'] ?? null) !== 'N6'
                || ! array_key_exists($codigo, $municipios) || isset($vistos[$codigo])
                || ! is_array($serie) || count($serie) !== 1
                || ! is_string($valor) || preg_match('/^(0|[1-9][0-9]{0,13})(\.[0-9]{1,4})?$/D', $valor) !== 1) {
                throw new DomainException('Renda com município, período, valor ausente/inválido ou duplicado. Nenhum dado foi gravado.');
            }

            $vistos[$codigo] = true;
            $linhas[] = ['municipio_id' => $municipios[$codigo], 'codigo_ibge' => $codigo, 'valor' => $valor];
        }

        // As 92 linhas únicas precisam corresponder ao catálogo inteiro, sem aproximação por nome.
        if (array_diff(array_keys($municipios), array_keys($vistos))) {
            throw new DomainException('Os códigos municipais da renda não correspondem ao catálogo local.');
        }

        return $linhas;
    }

    public static function urlValores(): string
    {
        return self::URL_BASE.'/periodos/2022/variaveis/13431?'.http_build_query(self::FILTROS);
    }
}
