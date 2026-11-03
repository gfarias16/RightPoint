-- Preparacao apenas: conferir a publicacao, validar SQL e obter estimativa/teto.
-- Nao executar sem autorizacao especifica para o custo desta nova consulta.
-- Quatro CNAEs separados; 9602501 nao isola exclusivamente barbearias.
-- Municipio ausente no resultado nao significa zero ou cobertura comprovada.
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
  AND cnae_principal IN ('5611201', '1091102', '4721102', '9602501')
  AND situacao_cadastral = 'Ativa'
GROUP BY
  uf,
  codigo_municipio,
  municipio,
  cnae_principal,
  situacao_cadastral
ORDER BY cnae_principal, municipio, codigo_municipio;
