<?php

namespace App\Services;

use App\Models\Municipio;
use DomainException;

class ValidadorContextoTerritorial
{
    public function validar(mixed $lote): array
    {
        if (! is_array($lote) || ! is_string($lote['tipo'] ?? null)) {
            throw new DomainException('Esperado objeto de contexto com tipo textual.');
        }
        $hosts = [
            'osm' => ['overpass-api.de', 'www.openstreetmap.org', 'openstreetmap.org'],
            'trabalho-rais' => ['www.gov.br', 'pdet.mte.gov.br', 'ftp.mtps.gov.br'],
            'trabalho-caged' => ['www.gov.br', 'pdet.mte.gov.br', 'ftp.mtps.gov.br'],
            'setores-ibge' => ['www.ibge.gov.br', 'ftp.ibge.gov.br', 'geoftp.ibge.gov.br'],
            'estabelecimentos' => ['opencnpj.org', 'www.gov.br', 'dados.gov.br'],
        ];
        $tipo = $lote['tipo'] ?? '';
        $codigo = $lote['codigo_ibge'] ?? '';
        $url = $lote['fonte_url'] ?? '';
        $periodo = $lote['periodo_referencia'] ?? null;
        if (! is_array($lote) || ! isset($hosts[$tipo]) || ! is_string($codigo) || ! preg_match('/^33[0-9]{5}$/D', $codigo)
            || ! Municipio::where('codigo_ibge', $codigo)->where('uf', 'RJ')->exists()
            || ! is_string($url) || strlen($url) > 500 || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || ! in_array(parse_url($url, PHP_URL_HOST), $hosts[$tipo], true) || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null
            || ! is_string($lote['versao_fonte'] ?? null) || trim($lote['versao_fonte']) === '' || strlen($lote['versao_fonte']) > 120
            || ! is_string($lote['coletado_em'] ?? null) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $lote['coletado_em'])) {
            throw new DomainException('Tipo, município, URL de origem, versão ou instante UTC inválidos.');
        }
        $data = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $lote['coletado_em'], new \DateTimeZone('UTC'));
        if (! $data || $data->format('Y-m-d\TH:i:s\Z') !== $lote['coletado_em']) {
            throw new DomainException('Data de coleta inválida.');
        }
        if (($tipo === 'setores-ibge' && $periodo !== '2022')
            || ($tipo === 'trabalho-rais' && (! is_string($periodo) || ! preg_match('/^20\d{2}$/D', $periodo)))
            || ($tipo === 'trabalho-caged' && (! is_string($periodo) || ! preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/D', $periodo)))
            || ($periodo !== null && (! is_string($periodo) || strlen($periodo) > 20))) {
            throw new DomainException('Referência incompatível com a fonte.');
        }
        $dados = $lote['dados'] ?? null;
        if (! is_array($dados)) {
            throw new DomainException('Dados de contexto ausentes.');
        }
        if (str_starts_with($tipo, 'trabalho-')) {
            $permitidos = $tipo === 'trabalho-rais' ? ['vinculos_ativos' => 'Vínculos', 'remuneracao_media' => 'Reais'] : ['admissoes' => 'Vínculos', 'desligamentos' => 'Vínculos'];
            $saida = [];
            foreach ($dados['indicadores'] ?? [] as $linha) {
                $indicador = $linha['codigo'] ?? '';
                $valor = $linha['valor'] ?? null;
                if (! isset($permitidos[$indicador]) || isset($saida[$indicador]) || ($linha['unidade'] ?? null) !== $permitidos[$indicador]
                    || ! is_numeric($valor) || ! is_finite((float) $valor) || $valor < 0 || $valor > 1e13
                    || ($indicador !== 'remuneracao_media' && (float) $valor !== (float) (int) $valor)) {
                    throw new DomainException('Indicador de emprego inválido, duplicado ou com unidade divergente.');
                }
                $saida[$indicador] = ['codigo' => $indicador, 'valor' => (float) $valor, 'unidade' => $permitidos[$indicador]];
            }
            if (count($saida) !== count($permitidos)) {
                throw new DomainException('Indicadores de emprego incompletos.');
            }
            $dados = ['indicadores' => array_values($saida), 'aviso' => 'Emprego formal; não representa fluxo de consumidores.'];
        } elseif ($tipo === 'setores-ibge') {
            $features = $dados['features'] ?? [];
            if (! is_array($features) || ! $features || count($features) > 3000) {
                throw new DomainException('Esperados entre 1 e 3000 setores para o piloto.');
            }
            $vistos = [];
            $saida = [];
            foreach ($features as $f) {
                $id = $f['properties']['codigo_setor'] ?? '';
                $pop = $f['properties']['populacao'] ?? null;
                if (($f['type'] ?? '') !== 'Feature' || ! is_string($id) || ! preg_match('/^'.$codigo.'\d{8}$/D', $id) || isset($vistos[$id])
                    || ($pop !== null && (! is_int($pop) || $pop < 0 || $pop > 10000000))) {
                    throw new DomainException('Setor, população suprimida ou vínculo municipal inválidos.');
                }
                $vistos[$id] = true;
                $saida[] = ['type' => 'Feature', 'properties' => ['codigo_setor' => $id, 'populacao' => $pop], 'geometry' => app(GeometriaMunicipal::class)->validar($f['geometry'] ?? null)];
            }
            $dados = ['type' => 'FeatureCollection', 'features' => $saida, 'aviso' => 'Recorte importado; ausência/supressão é NULL. Sem distribuição automática para H3.'];
        } else {
            $geometria = app(GeometriaMunicipal::class)->validar($dados['geometria_municipio'] ?? null);
            $pontos = $dados['pontos'] ?? null;
            if (! is_array($pontos) || count($pontos) > 3000) {
                throw new DomainException('Lote de pontos ausente ou acima do limite do piloto.');
            }
            $vistos = [];
            $saida = [];
            foreach ($pontos as $p) {
                $id = $p['id'] ?? '';
                $lat = $p['latitude'] ?? null;
                $lng = $p['longitude'] ?? null;
                if (! is_string($id) || $id === '' || strlen($id) > 80 || isset($vistos[$id]) || ! is_numeric($lat) || ! is_numeric($lng)
                    || ! is_finite((float) $lat) || ! is_finite((float) $lng) || ! app(GeometriaMunicipal::class)->contem($geometria, (float) $lng, (float) $lat)
                    || ! in_array($p['tipo'] ?? '', $tipo === 'osm' ? ['escola', 'saude', 'transporte'] : ['estabelecimento'], true)
                    || ($tipo === 'estabelecimentos' && (! preg_match('/^[0-9]{7}$/D', $p['cnae'] ?? '') || ($p['geocodificacao'] ?? '') !== 'validada'))) {
                    throw new DomainException('Ponto inválido, repetido, fora da área ou sem geocodificação validada.');
                }
                $vistos[$id] = true;
                $saida[] = ['id' => $id, 'latitude' => (float) $lat, 'longitude' => (float) $lng, 'tipo' => $p['tipo'], 'cnae' => $p['cnae'] ?? null];
            }
            $dados = ['geometria_municipio' => $geometria, 'pontos' => $saida, 'aviso' => 'Pontos do recorte importado; não representam cobertura completa ou fluxo de consumidores.'];
        }

        return ['tipo' => $tipo, 'codigo_ibge' => $codigo, 'fonte_url' => $url, 'periodo_referencia' => $periodo,
            'versao_fonte' => $lote['versao_fonte'], 'coletado_em' => $data->format('Y-m-d H:i:s'), 'dados' => $dados];
    }
}
