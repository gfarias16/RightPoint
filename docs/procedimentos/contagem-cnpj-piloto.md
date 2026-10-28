# Contagem CNPJ do piloto — Maricá/restaurantes

Atualizado em 28/10/2026. **Agregado importado pelo usuário e conferido no MySQL: uma coleta com quantidade 620, registros 620 e ausentes 0.** Repetição sem duplicação observada. Leitura na consulta demonstrativa implementada e testada por HTTP; conferência visual pendente. Job com 2,96 GB processados/faturados conforme tela; valor monetário e versão/período da fonte pendentes.

**Autorização utilizada em 28/10/2026:** usuário autorizou uma única execução do SQL abaixo, estimado em 2,96 GB, com teto de `3221225472` bytes (3 GiB) e possível cobrança. A captura posterior confirma teto e resultado. Essa autorização foi utilizada e não inclui repetições, consultas diferentes, aumento de teto, importação ou operação da conta pelo agente.

## Objetivo

Trocar o teste de 10 registros por uma contagem de todos os identificadores distintos que atendam ao recorte na versão consultada do OpenCNPJ. Isso mede a base da fonte, não comprova por si só todos os negócios existentes na realidade.

- Tabela: `opencnpj-bigquery.public.receita`, localização observada `us-east1`.
- UF RJ, município Receita `5853` (Maricá; IBGE `3302700`).
- CNAE principal `5611201`, situação textual `Ativa`.
- Sem `LIMIT`, contatos, QSA ou exportação de CNPJs individuais. Lista detalhada de estabelecimentos e geolocalização são etapas diferentes.

## SQL para validação e estimativa

```sql
SELECT
  COUNT(*) AS registros_no_recorte,
  COUNT(DISTINCT cnpj) AS cnpjs_distintos,
  COUNTIF(cnpj IS NULL OR TRIM(cnpj) = '') AS cnpjs_ausentes
FROM `opencnpj-bigquery.public.receita`
WHERE uf = 'RJ'
  AND codigo_municipio = '5853'
  AND cnae_principal = '5611201'
  AND situacao_cadastral = 'Ativa';
```

`COUNT(DISTINCT ...)` conta identificadores diferentes e ignora NULL, mas não exclui automaticamente strings vazias. A terceira coluna detecta ausências antes de aceitar a contagem. Não normalizamos ou convertemos códigos para número; inconsistências exigem investigação, não correção silenciosa.

## Próximo passo do usuário

1. Definir a persistência do agregado por município + CNAE + fonte + critério + referência da consulta. A tabela atual `indicadores_municipais` não possui vínculo com atividade; não gravar uma contagem por CNAE como se fosse população ou indicador sem atividade.
2. Preparar uma migration própria apenas após revisar campos, integridade e reimportação. Criar arquivo de migration não aplica alterações ao banco; consultar o estado local e obter validação antes de executar `migrate`.
3. Conferir versão/data da tabela consultada antes da gravação e guardar junto ao resultado o recorte, critério, fonte e data da consulta. A data do job não é a competência da base; não reaproveitar automaticamente a data de uma captura antiga como versão desta execução.

## Arquivo e job conferidos

- Arquivo `Right_Point/storage/app/amostras/contagem-marica-5611201.json`, 90 bytes, ignorado pelo Git. Lista JSON com um objeto; três campos presentes como strings decimais `620`, `620`, `0`, conferidos em PHP contra o resultado da captura. Na futura importação, validar tipo/formato e limites antes de converter contagens para inteiros.
- SHA-256 local: `8fa2f578f4a10f8c347ee1a49558313bd781841b77b920b6f821e6b8e64b7bff`. Identifica a cópia, não autentica a fonte. Exportar para a pasta não significa importar no MySQL.
- Captura mostra SQL completo coincidente com este procedimento, região `us-east1`, criação/início em **28/10/2026 12:08:25 UTC−3**, duração **387 ms**, prioridade interativa, SQL legado `false` e tabela de destino temporária.
- **2,96 GB processados e 2,96 GB faturados**, nas unidades/arredondamento exibidos. Agora são dados do job, não somente a estimativa do editor. O campo explícito de cache não aparece; não registrar `cacheHit` como confirmado. Não foram fornecidos bytes exatos nem valor em reais: consumo faturável não comprova desembolso após eventuais franquias/créditos.
- E-mail, ID do job e identificadores privados da conta não foram copiados para esta documentação. A captura não informa a versão/data de atualização atual da tabela-fonte.

## Resultado observado e limites

- 620 linhas e 620 valores CNPJ distintos no recorte da fonte; nenhuma repetição exata identificada por essa contagem.
- 0 identificadores NULL ou vazios após TRIM; isso não valida formato/dígitos verificadores dos 620 identificadores.
- Teto de 3 GiB visível e execução com sucesso. Consumo do job conferido acima; valor monetário e campo explícito de cache não confirmados.
- Não comprova cobertura de atividades informais, CNAEs secundários/equivalentes, demais municípios ou funcionamento efetivo de cada estabelecimento. É o total do recorte na base consultada, não garantia de concorrência real completa nem de chance de sucesso.

Se uma nova execução for necessária, repetir a revisão do SQL, teto e estimativa e obter autorização específica antes de executar; não aumentar ou remover teto automaticamente.

## Antes de usar o resultado no diagnóstico

- Exigir `cnpjs_ausentes = 0`; se não for zero, investigar antes de publicar o total.
- Se registros e identificadores distintos diferirem sem ausências, investigar duplicatas e contrato da fonte. Não contar linhas duplicadas como estabelecimentos diferentes.
- Não inferir cobertura de todos os CNAEs/municípios a partir deste teste. Conferir procedência, edição e condições da fonte; zero não deve virar ausência real de concorrência sem cobertura comprovada.
- A regra de concorrência (ativo + CNAE principal exato) é a proposta inicial, não equivalência comercial universal ou fórmula de score.
- Importador e persistência do agregado estão implementados. Na leitura pelo diagnóstico, informar fonte, instante da consulta e versão/período desconhecidos, sem converter essa ausência em referência inventada ou score validado.

## Estrutura de persistência — revisão antes da aplicação

Usuário gerou, preencheu e aplicou a migration após revisão. Agente confirmou oito migrations Ran (nova no batch 4), tabela `contagens_estabelecimentos` com 0 registros, colunas previstas, índice único da coleta e três FKs com DELETE RESTRICT. Isso não importa os 620 nem valida um score. Os arquivos usam `contagens_estabelecimentos` explicitamente no model e up/down, mesmo que o arquivo gerado tenha `contagem` no nome. Não gerar outra migration para corrigir apenas esse nome.

- Referências obrigatórias: `municipio_id`, `atividade_economica_id` e `fonte_dado_id`, com chaves estrangeiras e exclusão restrita.
- Contagens: `quantidade` corresponde a CNPJs distintos; `registros_no_recorte` e `cnpjs_ausentes` preservam controles da coleta, sem valores padrão que disfarcem ausência. Inteiros unsigned no MySQL; importador deverá validar limites e relações entre contagens.
- `criterio` textual identifica a regra versionada; para o piloto, a proposta é `ativos_cnae_principal_exato_v1`. Regra/filtros não são escolhidos livremente por dados externos; validar no importador.
- `consultado_em`: instante da consulta, armazenado em UTC. O job informado corresponde a 28/10/2026 12:08:25 UTC−3 (15:08:25 UTC). Não usar a hora de cada reimportação para identificar a mesma coleta.
- `periodo_referencia` e `versao_fonte`: nullable enquanto não confirmados, sem inventar competência. Data da consulta não substitui essas informações; ausências serão exibidas e não validam score.
- Chave única: município + atividade + fonte + critério + consultado_em. Uma nova consulta cria nova coleta; repetir a mesma não deve duplicar nem sobrescrever valores divergentes silenciosamente. A checagem de divergência/idempotência cabe ao futuro importador; não está implementada pelo índice sozinho.
- Model: tabela/fillable explícitos, casts de contagens e data, três relacionamentos belongsTo. Fillable/casts não substituem validação, integridade ou autorização.
- Rollback proposto remove apenas a tabela de contagens criada; isso removerá seus dados se for aplicado depois de carregada. Não executar migrate/rollback nesta etapa de preenchimento e revisão.

Revisão de 28/10: 14 verificações avulsas aprovadas em SQLite na memória, com dados fictícios, cobrindo inserção, casts, referências nullable, três relacionamentos, unicidade da coleta, três FKs inválidas, três exclusões restritas, outra coleta e rollback. SQL MySQL gerado com `--pretend`, não executado; pais reais conferidos como InnoDB/bigint unsigned. A execução em memória não testa unsigned ou todos os comportamentos do MySQL e não criou testes automatizados versionados. Não houve aplicação/rollback no MySQL nem execução da suíte completa.

Aplicação posterior realizada pelo usuário e confirmada por leitura do banco, sem mudança de código pelo agente. Usuário implementou a etapa de leitura/validação e simulação de `ImportarContagemCnpj`, repetida pelo agente com sucesso: 620/620/0, sem banco ou gravação. Valida três contagens textuais canônicas entre 0 e 4294967295, ausência de nulos/vazios, igualdade entre linhas/distintos e hash do texto normalizado para LF. Hash identifica o conteúdo observado, não comprova autenticidade, cobertura ou contexto por si só. Importador desta etapa é restrito ao piloto, não ferramenta genérica para outras coletas.

Gravação implementada posteriormente pelo usuário: opção `--gravar`, IDs resolvidos por códigos e transação de fonte/contagem. Fonte `opencnpj-receita`/`https://opencnpj.org/`, criada se ausente. Código bloqueia a linha da fonte e localiza a coleta pela chave; mantém contagens iguais ou rejeita divergência com DomainException, sem sobrescrever metadados. Transação com até três tentativas para deadlock, sem consultas externas. Padrão continua simulação após leitura do catálogo. Período/versão ficam NULL enquanto desconhecidos.

## Carga concluída e retomada

- Em 28/10/2026, usuário executou `php artisan cnpj:importar-contagem --gravar`: primeira carga concluída e segunda execução sem alteração. Agente confirmou por SELECT uma única coleta, fonte/códigos corretos, controles `620/620/0`, critério `ativos_cnae_principal_exato_v1` e consulta `2026-10-28 15:08:25` UTC; sintaxe válida. Não foram testados concorrência, divergência ou rollback do caminho de escrita nesta etapa, nem repetida a suíte completa.
- Outra máquina: código/migration pelo Git, mas banco e JSON ignorado precisam ser conferidos separadamente. Consultar `migrate:status` antes de aplicar a migration necessária; transportar o agregado original, sem repetir consulta paga. Executar primeiro `php artisan cnpj:importar-contagem` (simulação); usar `--gravar` somente ao decidir carregar aquele banco local.
- Leitura implementada pelo agente com autorização: `/consulta-demonstrativa` seleciona a coleta mais recente por município/CNAE, fonte OpenCNPJ e critério do piloto. Retorna objeto `concorrentes` com quantidade/procedência ou NULL quando não há coleta, sem acessar fonte externa. JavaScript exibe contagem da base, fonte, data UTC e referência desconhecida; outros recortes sem carga são indisponíveis, não zero. Não implementa score ou H3.
- Validação posterior: HTTP real Maricá/5611201 retornou 620 e score NULL; suíte completa com 30 testes e 117 asserções aprovada (casos novos em memória). Sintaxe PHP/JS válida. Validação visual bloqueada pela permissão de localhost, sem contorno; conferir manualmente o resultado em desktop/mobile.

## Referências

- [BigQuery — COUNT e COUNTIF](https://docs.cloud.google.com/bigquery/docs/reference/standard-sql/aggregate_functions).
- [BigQuery — estimativa e controle de custos](https://docs.cloud.google.com/bigquery/docs/best-practices-costs).
- [Evidências do contrato e do código municipal](../FONTES_DADOS.md#resultado-municipal-recebido-e-próximo-teste--27102026).
