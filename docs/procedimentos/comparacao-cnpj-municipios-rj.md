# Comparação municipal CNPJ — restaurantes no RJ

Atualizado em 28/10/2026. **Verificador/importador implementados; o usuário carregou o lote no MySQL deste computador e o agente conferiu 92 coletas, soma 39.423.** O agente não executou a escrita. Job confirmou SQL, instante e 3,81 GB processados/faturados. Teto/cache/custo monetário e versão/período não confirmados. Sem score. Etapas de aquisição abaixo são históricas: não repetir consultas.

## Importação local do lote

Neste computador, a migration de contagens foi aplicada pelo usuário e o lote foi gravado após validação e simulação. Leitura posterior encontrou 92 coletas em 92 municípios, soma 39.423, fonte `opencnpj-receita`, CNAE `5611201`, critério `ativos_cnae_principal_exato_v1` e consulta UTC `2026-10-28 20:01:33`. A rota demonstrativa respondeu com 308 em Barra Mansa e 620 em Maricá, mantendo score NULL e avisos. As instruções abaixo continuam úteis em outro banco local; a escrita desta cópia já foi feita.

Dentro de `Right_Point/`, substituindo o caminho pelo JSON transportado:

```powershell
php artisan cnpj:verificar-contagens-rj "caminho/do/lote.json"
php artisan cnpj:importar-contagens-rj "caminho/do/lote.json"
```

Devem aprovar 92 municípios/soma 39.423 sem gravar. O importador exige hash LF/instante UTC em `config/contagem_cnpj_rj.php`, nunca nome do arquivo ou `now()`. Se cache já estiver ativo, atualizar após o pull. Não alterar hash para aceitar outra exportação sem revisar arquivo, SQL/job e metadados em conjunto.

**Somente ao decidir carregar aquele banco local**, após conferir `migrate:status` e a simulação:

```powershell
php artisan cnpj:importar-contagens-rj "caminho/do/lote.json" --gravar
```

- Fonte e coletas na mesma transação. Bloqueio da fonte serializa importadores cooperantes; índice único protege a identificação. Compara todas as coletas existentes antes de inserir; divergência aborta sem sobrescrever. Falha posterior desfaz o lote/fonte nova.
- Repetição idêntica preserva valores, timestamps e metadados enriquecidos; lote parcialmente existente com valores iguais recebe apenas coletas faltantes. O instante novo `2026-10-28 20:01:33` UTC preserva o piloto `15:08:25` UTC como histórico. Período/versão NULL em novas linhas.
- Sem coletas, cria 92. Se o piloto já existir, cria 92 novas e totaliza 93. Repetição informa lote já cadastrado. Diagnóstico lê a coleta mais recente de cada município/CNAE, com avisos e score NULL.
- **85 testes/447 asserções aprovados** na implementação, incluindo 14 novos do importador: preservação do piloto, repetição, divergência, rollback na 47ª inserção, metadados, hash/UTC/CRLF. Esses testes usaram SQLite na memória; concorrência entre escritores no MySQL real não foi testada. A simulação inicial preservou JSON/banco do trabalho; a carga posterior neste computador foi conferida por leitura do MySQL e pela rota.
- `ValidadorContagensRj` é compartilhado pelos dois comandos, evitando regras divergentes. O comando singular `cnpj:importar-contagem` continua restrito ao piloto; usar o plural para o lote.

Na escola, seguir [roteiro de ambiente](./ambiente-multiplos-computadores.md#retomada-da-entrega-cnpj--28102026). Git não transporta JSON, dependências, cache ou banco; levar o arquivo original separadamente. Sem novo SQL pago.

## Verificação local implementada

Dentro de `Right_Point/`:

```powershell
php artisan cnpj:verificar-contagens-rj "caminho/do/lote.json"
```

Substituir pelo caminho do JSON original exportado na pasta amostras. O nome automático foi preservado; não é necessário renomear ou repetir a aquisição. Em outro computador, transportar o arquivo ignorado pelo Git, conferir catálogos e atualizar cache de configuração se já estiver ativo. `php artisan help cnpj:verificar-contagens-rj` mostra o argumento obrigatório; não existe opção de gravação.

- Verifica arquivo até 1 MiB, lista de exatamente 92 grupos, oito campos textuais, códigos/filtros, contagens decimais canônicas unsigned, ausentes zero e linhas=distintos; aceita zero explícito sem apresentá-lo como ausência real. Não completa municípios ausentes nem aceita resultado parcial como lote completo.
- `config/municipios_receita.php` contém 92 pares explícitos, preservando códigos com zero inicial. Rejeita códigos desconhecidos, mapas incompletos/IBGE duplicado e referências ausentes no catálogo. Nomes são comparados após normalização de acentos/separadores, **depois** de selecionar o código configurado; não há pesquisa por semelhança. Exceção restrita: Receita `5875`/PARATI e IBGE `3303807`/Paraty.
- Só exibe tabela de sucesso depois de validar todos os grupos; mostra soma e hash LF, sem autenticar arquivo, fixar edição ou associar automaticamente uma nova exportação ao job antigo. Não grava fonte, município, atividade ou contagem, não acessa rede e não aceita `--gravar`.
- Conferência real aprovada: 92 registros, soma 39.423, hash coincidente e saída 0. Arquivo e banco preservados antes/depois: fontes/coletas iguais, 92 municípios, 1.332 atividades e uma coleta do piloto. O lote dos demais municípios ainda não aparece no diagnóstico.
- 41 testes novos em memória com arquivos fictícios: validação positiva/negativa, Parati, zeros iniciais, limites/controles, lote incompleto, mapa/catálogo, falha de banco e preservação de coleta. Suíte completa: **71 testes, 342 asserções aprovados**; sintaxe e Pint válidos. Não testa importação em lote, ainda inexistente.
- Etapa posterior concluída: importador plural disponível conforme seção inicial. Primeira carga real depende da decisão do usuário no banco local. Não usar o importador fixo de Maricá para o lote.

## Arquivo recebido e conferido

- JSON com nome automático gerado pelo BigQuery em `Right_Point/storage/app/amostras/`, preservado sem renomear/sobrescrever, ignorado pelo Git. Arquivo de 20.950 bytes, lista com 92 objetos e oito campos textuais do SQL. Nome com identificador de job não copiado para documentação pública.
- SHA-256 bruto e normalizado LF: `d691c76ace7d6a8f03abd172fe0ce40f8269fe067870bdcb7a9ba7b2b7f68467`. Identifica a cópia; não autentica a fonte ou comprova o SQL executado.
- Todos os grupos RJ/5611201/Ativa, código Receita textual de quatro dígitos, nome preenchido e contagens decimais canônicas no intervalo unsigned de 32 bits. 92 códigos e nomes distintos; nenhuma repetição municipal. Em todos: linhas = distintos e ausentes = 0. Soma municipal 39.423; não é checagem de unicidade de CNPJs entre municípios. Maricá/5853 continua 620.
- Catálogo MySQL local lido, sem escrita: 92 municípios. Normalização de acentos/separadores encontrou 91 candidatos únicos e 91 IBGEs distintos. A normalização foi usada para conferência, não para criar vínculos automaticamente. Única diferença de grafia: `5875`/`PARATI` frente a Paraty/`3303807`. [IBGE: códigos municipais](https://www.ibge.gov.br/explica/codigos-dos-municipios.php) e [alterações toponímicas](https://www.ibge.gov.br/geociencias/organizacao-do-territorio/estrutura-territorial/27336-alteracoes-toponimicas-municipais.html?lang=pt-BR) corroboram código/nome e variação Parati–Paraty nos resultados indexados consultados; abertura direta destas páginas retornou 403. Registrar exceção explícita na futura proposta de mapa, não usar aproximação genérica.
- Na conferência inicial, configuração continha apenas Maricá. Foi ampliada posteriormente para 92 pares com o verificador/testes descritos acima. Banco continua com uma coleta do piloto; os 92 grupos não foram importados.
- O arquivo contém dados agregados, **não metadados do job**; esses foram conferidos nas capturas posteriores abaixo. Não usar hora do arquivo, nome do job, estimativa ou dados antigos como competência/instante desta coleta. Não repetir SQL; preservar o resultado original.

## Job conferido nas capturas

- SQL coincide com a consulta preparada: tabela pública, RJ/5611201/Ativa, cinco campos de agrupamento, três controles e ordenação municipal, sem LIMIT.
- Local `us-east1`. Criação **28/10/2026 17:01:32 UTC−3**; início **17:01:33 UTC−3**; término **17:01:34 UTC−3**; duração **1 s 36 ms**. Para identificar a futura coleta, usar o início convertido para **`2026-10-28 20:01:33` UTC**, não a hora de importação. Preservar separadamente a coleta anterior de Maricá.
- **3,81 GB processados e 3,81 GB faturados**, nas unidades/arredondamento da interface. São consumo reportado, não custo em reais ou bytes exatos; franquias/créditos não avaliados.
- Prioridade INTERACTIVE, SQL legado false, tabela temporária. Modo de índice UNUSED não informa se houve cache; `cacheHit` e teto máximo não aparecem. Não inferir esses valores.
- E-mail e IDs privados do job/projeto não copiados para os docs. Falta conferir Detalhes da tabela pública; última modificação não é necessariamente competência. Se não houver evidência de edição/período, manter desconhecido/NULL em vez de usar a data do job.

## 1. Conferir a publicação, sem executar SQL

**Conferência concluída pelas capturas do usuário:** tabela `opencnpj-bigquery.public.receita`, local `us-east1`, criada em 09/06/2026 16:12:43 UTC−3, última modificação em **14/10/2026 06:44:25 UTC−3** (`2026-10-14 09:44:25` UTC). 73.797.761 linhas, 44,82 GB lógicos e 5,09 GB físicos (valores exibidos). Descrição vazia, sem edição/período declarados. Portanto, `periodo_referencia` e `versao_fonte` permanecem NULL; metadados da tabela ficam registrados aqui como evidência, não como competência inventada ou prova de imutabilidade histórica. Não é necessário repetir consulta ou obter outra captura para iniciar o verificador local.

No BigQuery, abra `opencnpj-bigquery` → `public` → `receita` → **Detalhes**. Envie um recorte com ID da tabela pública, última modificação, localização, número de linhas e eventual descrição da edição. Evite incluir e-mail, projeto de faturamento ou identificadores da conta.

- Última modificação é metadado da tabela, **não necessariamente competência dos dados da Receita**. Descrição automática/gerada por IA não é evidência de edição.
- Se o publicador identificar edição/competência e seu vínculo com essa tabela, registrar valor e fonte da evidência. Caso contrário, continuar como `A CONFIRMAR`/NULL; não inventar mês de referência.
- O [GET /info do OpenCNPJ](https://api.opencnpj.org/info) pode ajudar a identificar a publicação do serviço, mas não prova sozinho que a tabela BigQuery usa a mesma edição. Não copiar o identificador do serviço para `versao_fonte` sem estabelecer esse vínculo.
- Uma observação atual não identifica retroativamente a versão da coleta já importada de Maricá. Não atualizar seus metadados com base apenas nessa nova captura. Conferir detalhes antes e depois de eventual execução; se mudarem durante a coleta, investigar antes de publicar comparação.

## 2. Preparar a consulta e enviar a estimativa

1. Abra uma nova aba SQL no BigQuery, com SQL legado desmarcado.
2. Copie integralmente [cnpj-rj-5611201.sql](../consultas/cnpj-rj-5611201.sql).
3. Aguarde a validação automática do editor e a mensagem de bytes estimados. **Não clique em Executar.**
4. Confira o campo **Máximo de bytes cobrados** da mesma aba; não presuma que herdou o teto anterior. Envie a estimativa/erro e o teto atual para revisão.
5. Só depois da revisão, escolher teto compatível e autorizar expressamente uma execução. Não elevar/remover limite nem repetir consulta automaticamente. A autorização do piloto de Maricá já foi utilizada.

O SQL mantém `uf = 'RJ'`, CNAE principal `5611201` e situação textual `Ativa`, sem filtro de um único município e sem `LIMIT`. Projeta apenas identificação municipal/filtros e contagens, sem contatos, QSA ou exportação de CNPJs individuais. Agrupar em uma consulta evita planejar uma execução por cidade; **não promete custo igual ou menor que o piloto**: há colunas adicionais e nova estimativa a conferir. `LIMIT` não é substituto do teto de bytes. [Controle de custos do BigQuery](https://docs.cloud.google.com/bigquery/docs/best-practices-costs).

## 3. Contrato e controles do resultado futuro

- Uma linha por combinação exata de UF, código Receita, nome municipal, CNAE principal e situação. Os nomes são preservados para conferência; variantes podem produzir mais de uma linha para o mesmo código. Não somar/mesclar silenciosamente.
- `registros_no_recorte`: linhas na fonte; `cnpjs_distintos`: CNPJs distintos; `cnpjs_ausentes`: NULL/vazio/somente espaços. Exigir ausentes zero e linhas iguais aos distintos antes de aceitar cada grupo.
- Preservar códigos como texto, inclusive zeros iniciais. Contagens podem vir como strings no JSON exportado: validar antes de converter. Não há validação individual de formato/dígitos verificadores ou de repetição do mesmo CNPJ entre municípios por estes agregados.
- Manter grupos com código/nome ausente visíveis para diagnóstico; eles não são importáveis. Não ocultá-los com filtro adicional.
- Não exigir nem prometer exatamente 92 linhas: municípios sem registros no recorte não aparecem, variantes podem dividir grupos, e códigos precisam de conciliação. Ausência de linha não comprova zero nem cobertura completa.
- Para cada grupo, conferir UF + código + nome contra o catálogo municipal. Os 92 pares desta exportação estão registrados explicitamente em configuração. Nome semelhante não autoriza vínculo automático; códigos desconhecidos/ambíguos impedem a aceitação do lote.
- Mesma extração/filtros facilitam comparação, mas não comprovam atualização individual, funcionamento real, equivalência comercial ou competência compatível com população do Censo 2022. Não derivar nota de sucesso nem classificar poucos concorrentes como oportunidade automaticamente.

## 4. Somente após autorização e execução

Salvar o resultado completo existente como `Right_Point/storage/app/amostras/contagem-rj-5611201.json`, sem sobrescrever o piloto `contagem-marica-5611201.json`. Conferir o formato real exportado (lista JSON ou NDJSON) e o hash local antes de orientar um leitor. Não executar novamente só para baixar.

Registrar metadados sanitizados: SQL utilizado, tabela, instante da consulta com fuso/UTC, publicação comprovada ou desconhecida, bytes processados/faturados e cache se visível. Não publicar e-mail, ID privado de job/projeto ou credenciais. Guardar resultado/metadados da mesma execução juntos, localmente; amostras não acompanham o Git.

**Não usar `cnpj:importar-contagem --gravar` para este lote.** O comando atual aceita apenas o arquivo/hash e a coleta fixa de Maricá. Primeiro revisar grupos, correspondências municipais e procedência; depois projetar leitor/importador em lote com simulação e transação. Preservar a coleta antiga: Maricá pode mudar em nova extração; não exigir 620 nem sobrescrever o histórico.

## Validação desta preparação

SQL revisado com o esquema previamente observado e a [documentação oficial de agregação](https://docs.cloud.google.com/bigquery/docs/reference/standard-sql/aggregate_functions). Seis verificações avulsas aprovadas em SQLite na memória, com dados fictícios e função COUNTIF registrada para compatibilidade: filtros, duplicatas/ausências, código textual com zero inicial, grupo válido, código ausente preservado e nomes divergentes não mesclados. Nenhum arquivo de testes persistente criado; isso não valida o dialeto/execução GoogleSQL ou a fonte real.

Validação GoogleSQL, permissões, custo e resultados reais ainda dependem do editor/conta do usuário; não houve acesso autenticado à conta ou execução paga pelo agente. `git diff --check` passou; suíte Laravel não repetida porque esta etapa só criou SQL documental/procedimento e atualizou docs, sem mudar o aplicativo.
