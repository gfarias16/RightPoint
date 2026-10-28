-- Preparacao: validar e obter estimativa no BigQuery antes de executar.
-- Uma execucao paga exige autorizacao especifica; nao herda o teto do piloto.
-- Codigo municipal e nome abaixo sao da Receita, nao codigos IBGE.
SELECT
  uf,
  codigo_municipio,
  municipio,
  cnae_principal,
  situacao_cadastral,
  COUNT(*) AS registros_no_recorte,
  COUNT(DISTINCT cnpj) AS cnpjs_distintos,
  COUNTIF(cnpj IS NULL OR TRIM(cnpj) = '') AS cnpjs_ausentes
FROM `opencnpj-bigquery.public.receita`
WHERE uf = 'RJ'
  AND cnae_principal = '5611201'
  AND situacao_cadastral = 'Ativa'
GROUP BY
  uf,
  codigo_municipio,
  municipio,
  cnae_principal,
  situacao_cadastral
ORDER BY municipio, codigo_municipio;
