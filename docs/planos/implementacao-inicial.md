# Plano de implementação inicial — RightPoint

Última atualização: 27/10/2026 (amostra OpenCNPJ/BigQuery conferida localmente; comando próprio de validação, cobertura e persistência pendentes).

## Objetivo

Dar continuidade à base Laravel e construir, em etapas verificáveis, o fluxo de consulta de potencial comercial por atividade econômica e área geográfica. Este plano registra a proposta de sequência; não transforma decisões pendentes em requisitos aprovados.

## Estado confirmado

- Repositório local: `C:\xampp\htdocs\RightPoint`.
- Aplicação Laravel: `Right_Point/` (renomeação intencional confirmada pelo usuário em 01/10/2026).
- O usuário informou que criou o projeto Laravel para o RightPoint.
- `Right_Point/composer.json` declara PHP `^8.2` e Laravel `^12.0`.
- `Right_Point/routes/web.php` contém a página de exploração, a consulta demonstrativa e a rota intermediária da malha do RJ.
- A aplicação contém a estrutura inicial do Laravel e os models `Municipio` e `AtividadeEconomica`, com migrations próprias para os dois catálogos.
- A documentação contém minimundo, regras de negócio, restrições de integridade e modelo conceitual de classes.
- Um registro de 01/10/2026 informa cinco migrations aplicadas e dois testes iniciais aprovados, mas não identifica com segurança a máquina. No notebook do trabalho, as mesmas cinco migrations foram aplicadas ao banco inicialmente vazio em 05/10/2026 e os dois testes iniciais também passaram. O frontend ainda não foi validado neste notebook. Consulte o [procedimento de ambiente local](../procedimentos/ambiente-multiplos-computadores.md).
- O banco de desenvolvimento padronizado é `rightpoint` (minúsculas), servido pelo MySQL/MariaDB do XAMPP. Neste notebook, a conexão verificada usa `127.0.0.1:3306`, usuário `root`, senha vazia e MariaDB 10.4.32; confira se a escola está configurada de modo equivalente antes de executar migrations lá.
- O `.env` foi incluído no commit por decisão explícita do usuário. O banco local, `vendor/` e `node_modules/` não são sincronizados pelo Git. A continuidade entre computadores deve seguir o [procedimento de ambiente local](../procedimentos/ambiente-multiplos-computadores.md).
- As migrations de municípios e atividades econômicas estão versionadas. No computador da escola, ainda é necessário verificar se existem migrations ou outros arquivos locais não enviados antes de qualquer sincronização.

O model `User` fornecido pelo Laravel não significa que login ou cadastro foram aprovados como funcionalidades do RightPoint.

## Referências para retomada

1. [Minimundo](../contexto/minimundo.md).
2. [Regras de negócio](../contexto/regras-de-negocio.md).
3. [Restrições de integridade](../contexto/restricoes-de-integridade.md).
4. [Modelo inicial de classes](../arquitetura/modelo-de-classes.md).
5. [Limitações conhecidas](../contexto/limitacoes-conhecidas.md).
6. [Matriz de dados e fluxograma do diagnóstico](../arquitetura/matriz-dados-diagnostico.md).
7. [Roteiro para a apresentação acadêmica](../APRESENTACAO.md): atualizar com fontes, estratégias, evidências, imagens e limitações a cada etapa relevante.

## Próxima etapa imediata

**Entrega para a escola — 27/10/2026:** usuário pediu adiar o teste de filtro divergente e preparar documentação/commits. Comando local e teste de duplicata estão conferidos; demais testes do novo comando permanecem pendentes, sem implementação automática nesta entrega. Na escola, primeiro conferir Git/ambiente e transportar as amostras ignoradas conforme [procedimento](../procedimentos/ambiente-multiplos-computadores.md#amostras-cnpj-na-retomada-da-escola--27102026). Depois alinhar aquisição completa dos estabelecimentos e mapeamento Receita↔IBGE antes de criar importador ou usar totais no diagnóstico. Não executar nova consulta paga, importação ou migration por inferência. Os parágrafos seguintes são históricos e não exigem retomar imediatamente os exercícios adiados.

**Caminho opcional e teste de duplicata concluídos — 27/10/2026:** usuário implementou argumento opcional e fixture fictícia local. Agente repetiu: duplicata retorna falha/código 1 e amostra padrão continua com 10 registros/código 0; sintaxe válida. Próximo exercício: testar UF divergente em arquivo separado; depois automatizar casos de erro (JSON inválido, campo ausente, filtro divergente, duplicata e arquivo inexistente), sem usar dados reais ou alterar a amostra. Nenhum importador ou nova aquisição autorizado nesta etapa. Registros seguintes são históricos.

**Validação por registro implementada — 27/10/2026:** sintaxe e execução conferidas pelo agente: 10 registros aprovados pelo comando nos campos, formato numérico, filtros e duplicatas. Próximo passo didático: usuário acrescentar caminho de arquivo opcional, mantendo a amostra padrão, e testar um arquivo fictício separado com duplicata. Esperar falha/código 1, sem alterar a amostra real. Esse teste negativo ainda não foi executado; cobertura, dígitos verificadores e persistência continuam pendentes. Registros seguintes são históricos.

**Leitura implementada — 27/10/2026:** usuário implementou `cnpj:verificar-amostra`; agente confirmou sintaxe e execução com 10 registros, sem API ou gravação. Próximo exercício: acrescentar validação por registro (objeto, cinco campos textuais, formato de CNPJ da amostra, filtros e duplicatas), falhando antes de declarar sucesso. O código dessa próxima etapa foi orientado, não implementado pelo agente. Depois testar entradas inválidas com dados sintéticos. Não importar ou usar a amostra como total de concorrentes. Registros seguintes são históricos.

**Prioridade após conferência do arquivo — 27/10/2026:** exportação local é **array JSON completo**, não NDJSON. Conferência avulsa em PHP confirmou 10 registros/CNPJs distintos, todos os campos textuais e filtros `RJ`/Receita `5853`/CNAE `5611201`/`Ativa`, sem divergências; um CNPJ tem zero inicial. Próximo exercício: usuário criar a estrutura `VerificarAmostraCnpj` pelo Artisan e implementar leitura/validação do arquivo completo, sem API ou banco. Nenhum comando foi criado pelo agente. Renomeação sugerida para `cnpj-marica-5611201.json` ainda não realizada. Cobertura, dígitos verificadores, termos da fonte e custo efetivo continuam pendentes. Amostra ignorada pelo Git. Registros seguintes são históricos.

**Prioridade após amostra recebida — 27/10/2026:** a captura do usuário confirma 10 linhas do SQL autorizado, com CNAE `5611201`, situação `Ativa` e código Receita `5853` visíveis. Não equivale ao total de concorrentes. Próximo passo: salvar os resultados existentes como JSON local (NDJSON), sem nova consulta, em `Right_Point/storage/app/amostras/cnpj-marica-5611201.json`; criar a pasta se necessário. Conferir o arquivo antes de orientar o exercício PHP de leitura/validação, preservando CNPJ textual e zeros iniciais, sem importação. O arquivo está ignorado pelo Git e não acompanhará o pull em outra máquina. Custo real, cobertura e mapeamento completo continuam pendentes; registros seguintes são históricos.

**Retomada após resultado municipal — 27/10/2026:** SQL autorizado retornou `MARICA/5853/RJ`. Código público do OpenCNPJ confirma referência da Receita, diferente do IBGE `3302700`. Após o usuário iniciar o MySQL, leitura local confirmou Maricá e CNAE `5611201` cadastrados, sem gravação. Próxima tarefa: colar o [SQL de amostra](../FONTES_DADOS.md#resultado-municipal-recebido-e-próximo-teste--27102026) com código Receita `5853`, UF RJ, CNAE principal `5611201` e situação `Ativa`, **apenas para estimativa**, mantendo teto de 2 GiB. A autorização anterior não abrange esse novo teste; não executar ou aumentar teto sem nova revisão/decisão. Ainda faltam resultado de estabelecimentos, cobertura, mapeamento dos 92 municípios e persistência. Registros seguintes são históricos.

**Teste municipal autorizado — 27/10/2026:** usuário aceitou elevar o teto para **2 GiB** e executar **uma vez** o SQL municipal com estimativa de 1,53 GB. Próximo passo prático: orientar o ajuste na mesma aba, conferir o SQL/estimativa e receber o resultado ou erro. Não há evidência de execução nem de novo teto salvo. Após o resultado, revisar a referência do código municipal antes de propor outro teste. A autorização não abrange outras consultas ou importações; itens seguintes preservam o histórico.

**Decisão pendente após estimativa — 27/10/2026:** captura confirma SQL municipal válido e teto de 1 GiB salvo no editor. A estimativa de 1,53 GB excede esse teto. O custo de referência público é da ordem de US$ 0,01 de processamento para esse teste, sem confirmação de cobrança efetiva ou preço em reais; não prometer gratuidade. Próximo passo: usuário decidir se aceita elevar o teto desta consulta para 2 GiB e executar somente a verificação municipal. Sem autorização, não aumentar limite nem executar; não há resultado ou mapeamento validado. Ver evidência/cautelas em `docs/FONTES_DADOS.md`. Registros seguintes são históricos.

**Avanço por capturas — 27/10/2026:** usuário confirmou acesso aos metadados de `opencnpj-bigquery.public.receita`, em `us-east1`. O esquema contém `codigo_municipio` STRING, mas ainda sem confirmação de referência Receita/IBGE. Próximo passo é conferir o teto de 1 GiB no editor original e colar o [SQL municipal proposto](../FONTES_DADOS.md#acesso-autenticado-e-esquema-vistos-em-capturas--27102026) somente para validação/estimativa, sem executar. Revisar bytes/erros antes de qualquer autorização de consulta. Ainda faltam comprovação do valor salvo, valores municipais reais, CNAE/situação, edição/cobertura e exportação; nenhuma tabela/importação Laravel foi criada. Os registros seguintes são históricos.

**Mudança de premissa — faturamento habilitado segundo o usuário (27/10/2026):** não presumir mais sandbox sem cobrança. Antes de SQL, orientar orçamento de alertas para o projeto, configurar/revisar teto de bytes faturados por consulta no modelo sob demanda e conferir esquema/estimativa. Alertas não interrompem o BigQuery; spend caps consultados não o incluem. O agente não recebeu autorização para gastos nem modificou configurações da conta. Depois das proteções, retomar a validação da tabela externa e do recorte RJ; nada foi importado.

**Ponto de retomada BigQuery — 27/10/2026:** leitura anônima da estrutura retornou 401; não foram confirmados acesso, esquema ou estimativa. Próxima ação: usuário acessar BigQuery Studio com a própria conta e informar se possui projeto em sandbox/sem faturamento. Não criar recursos, ativar cobrança ou usar credenciais automaticamente. Com acesso confirmado à tabela externa, revisar esquema/detalhes, preparar SQL mínimo e obter estimativa/dry run antes de qualquer consulta efetiva. `LIMIT` não é garantia de custo reduzido. Ver [registro da verificação](../FONTES_DADOS.md#bigquery--verificação-de-acesso-em-27102026). A importação e a escolha definitiva de fonte seguem pendentes.

**Prioridade atual — 27/10/2026:** o usuário autorizou avaliar o OpenCNPJ (`opencnpj.org`). Os [metadados e o contrato](../FONTES_DADOS.md#opencnpj--avaliação-autorizada-em-27102026) foram conferidos: ZIP nacional de 14,39 GB, sem recorte RJ identificado, e campos pertinentes com município por nome. Não aprovar a carga direta apenas pela existência de download gratuito. Próximo passo: avaliar acesso/contrato/estimativa de custo da tabela BigQuery indicada pelo projeto para um recorte pequeno, sem executar consultas pagas ou configurar contas automaticamente. Após viabilizar aquisição, validar amostra, mapeamento dos 92 municípios, procedência e cobertura antes de criar migration/importador. Nenhuma integração foi implementada. A investigação anterior do Mapa de Empresas permanece alternativa, não bloqueia esta avaliação. Registros seguintes são históricos.

**Retomada de 22/10/2026, notebook do trabalho:** pull confirmado em `a1d4446`. O banco deste notebook tem 1 fonte e 92 populações de 2022 (além de 92 municípios e 1.332 CNAEs), conforme leitura nesta sessão; não repetir a carga por causa do banco vazio relatado no outro ambiente. Próxima tarefa do usuário: seguir o [checklist do Mapa de Empresas](../FONTES_DADOS.md#checklist-de-validação-do-mapa-de-empresas--22102026) e trazer captura/exportação do recorte Maricá/`5611201`/ativos, verificando subclasse e CNAE principal. O manual de exportação foi consultado, mas os filtros interativos ainda não foram validados. Rever a evidência antes de escolher a fonte ou criar persistência. Os parágrafos seguintes registram a sessão anterior.

Comparação e captura de 21/10/2026: a documentação das quatro alternativas de CNPJ foi [comparada](../FONTES_DADOS.md#comparação-para-a-contagem-de-concorrentes), sem aprovação de fornecedor ou implementação. O Mapa de Empresas pode simplificar o **total municipal**, mas ainda precisa de teste manual do filtro pela subclasse CNAE exata e da exportação; arquivos oficiais permitem identificação individual, com custo de processamento; Casa dos Dados requer chave/saldo; Minha Receita não sustentou paginação completa após 429. Capturas do usuário mostram Barra Mansa (`3300407`) e CNAE `5250803` selecionados no RightPoint; o diagnóstico apresenta população ausente, CNPJ não integrado e nenhum score. Isso concorda com as 0 fontes/0 populações do banco deste computador, mas não explica a divergência com a carga registrada anteriormente. **Ao retomar no trabalho:** verificar Git e banco local antes do pull; para a população neste computador, seguir o [procedimento de conferência e carga](../procedimentos/ambiente-multiplos-computadores.md#população-do-censo-2022-em-outra-máquina) somente após confirmar a conexão e os 92 valores. Para CNPJ, confirmar no painel do Mapa o recorte Maricá/`5611201`/ativas e registrar a edição e os filtros. Nenhum código ou dado foi alterado nesta comparação.

Registro anterior de 21/10/2026: por autorização do usuário, o agente implementou o laço de 1 a 3 páginas, preservação dos filtros/cursor e controle de CNPJs/cursores repetidos na execução. Há uma tabela por página, sem gravação, retentativa ou contagem total. Sintaxe, ajuda, Pint e suíte passaram: 16 testes/47 asserções, sendo 11 casos do comando com HTTP simulado e SQLite em memória. Após o pull, as sete migrations locais apareceram como `Ran`, e a suíte passou novamente. **Teste real do usuário:** com `--paginas=2`, a primeira página retornou 2 registros e cursor, mas a segunda respondeu HTTP 429; o comando parou corretamente sem gravar. A avaliação da [fonte](../FONTES_DADOS.md) concluiu provisoriamente que a busca pública serve para exploração, mas não para contagem completa neste estágio. Neste banco local foram observados 0 fontes e 0 indicadores de população, em divergência com o registro anterior; investigar antes de repetir cargas. O [exemplo didático](./exercicio-paginacao-cnpj.md) é histórico e não deve sobrescrever a classe atual. O [roteiro da apresentação](../APRESENTACAO.md) resume estratégias e limitações. Os registros anteriores abaixo são históricos.

Identificação na tabela corrigida pelo usuário em 21/10/2026: uma linha por estabelecimento com cinco colunas e CNPJ preenchido como texto. Sintaxe e quatro testes HTTP simulados passaram para linhas, vazio, repetido na página e CNAE com zero inicial. Próxima ação: comparar CNPJs da página seguinte com os da primeira; em seguida implementar paginação automática limitada com controle de repetição entre páginas. Formato/dígitos verificadores do CNPJ, cobertura, procedência e persistência seguem pendentes.
Continuação manual concluída em amostra em 21/10/2026: o usuário implementou `--cursor` e mostrou a resposta de uma página seguinte com outro cursor. Sintaxe, ajuda e envio dos parâmetros em uma única chamada HTTP simulada passaram. Próximo exercício: conferir CNPJ como texto e detectar repetidos dentro da página; ainda faltam repetição entre páginas, paginação automática limitada, procedência, cobertura e persistência. As conferências anteriores abaixo preservam o histórico.
Ainda em 21/10/2026, o usuário conferiu 2 registros reais da primeira página: os filtros de UF, município e CNAE corresponderam, com uma situação ativa e outra inapta. Sintaxe e rejeição do código municipal de seis dígitos foram verificadas. O próximo exercício é permitir uma página seguinte via opção `--cursor`, sem alterar o token recebido e mantendo uma requisição por execução; a opção, a paginação automática, a deduplicação e a persistência ainda não estão implementadas.
Em 21/10/2026, no notebook do trabalho, o usuário criou `VerificarBuscaCnpj` e mostrou uma busca na Minha Receita com 2 registros e continuação, sem gravação. A revisão e os testes locais de sintaxe e argumentos inválidos passaram. A prioridade atual é validar UF, município IBGE, CNAE principal e situação de cada item; depois verificar paginação, duplicatas e procedência antes da importação. Os exercícios CSV abaixo são históricos e ficam como alternativa, sem exigir download de arquivos grandes nesta etapa.
Na escola em 20/10/2026, o checkout do XAMPP foi atualizado até `6085328` e o agente preparou o banco local: sete migrations executadas, 92 municípios, 1.332 CNAEs e 92 populações de 2022 com fonte e data. A busca e a consulta HTTP foram verificadas contra o MySQL real. O usuário informou que a conferência visual solicitada já foi feita no trabalho e pediu para avançar ao código. O usuário criou `VerificarEstabelecimentos` e compartilhou sintaxe e ajuda corretas; o agente conferiu os retornos para arquivo existente e inexistente. O usuário acrescentou a leitura de uma linha CSV, fechamento do arquivo e contagem dos campos; a sintaxe passou. O usuário moveu e preencheu a amostra técnica em `Right_Point/storage/teste-leitura.csv`; o agente executou com `storage/teste-leitura.csv` e confirmou 2 campos com saída 0. A leitura da amostra foi validada, sem representar o leiaute oficial da Receita. Depois, selecionar o arquivo oficial e investigar seu município/CNAE antes de implementar concorrência. Detalhes de retomada e do cache local estão no [STATUS.md](../STATUS.md). A suíte recebida usa SQLite em parte dos testes e não foi executada nesta preparação, respeitando a preferência por MySQL.

Em 20/10/2026, capturas do usuário confirmaram busca e seleção de CNAE no formulário, consulta demonstrativa de Maricá e um perfil salvo no navegador. O mapa e o CRUD continuam como protótipo local sem login; edição/exclusão do perfil, troca de base e responsividade ainda precisam de teste interativo. O importador gravou a população de 2022 para 92 municípios distintos neste notebook e a rota de consulta devolveu população, fonte e data para Maricá. Primeiro, conferir a tela após a carga; em seguida, investigar a fonte CNPJ/estabelecimentos e o vínculo entre seus códigos municipais e os códigos IBGE, antes de definir uma importação segura de concorrentes. Não apresentar score ou concorrentes fictícios enquanto faltarem base CNPJ e fórmula aprovada.

As migrations iniciais e os models dos catálogos estão disponíveis. Em 13/10/2026, o comando `ibge:importar-municipios-rj` passou a gravar os dados com transação e `upsert`; duas execuções reais no MySQL/MariaDB do XAMPP mantiveram 92 municípios do RJ, sem duplicatas e com IDs e datas de criação preservados. Em 14/10/2026, o usuário validou 92 códigos e valores de população de 2022 contra o catálogo local e conferiu nos metadados a variável 93 (`População residente`, unidade `Pessoas`). O model e a migration de indicadores municipais foram criados e a migration foi aplicada. Em 20/10/2026, o usuário executou o importador: leitura posterior encontrou 92 indicadores em 92 municípios distintos e uma fonte registrada em `fontes_dados`; a rota de consulta devolveu população, fonte e data para Maricá. A [matriz e o fluxograma](../arquitetura/matriz-dados-diagnostico.md) documentam o recorte RJ e critérios propostos, sem validar a fórmula. As próximas estruturas do domínio continuam pendentes.

### Decisão de persistência dos indicadores (14/10/2026)

Para a primeira versão, foi escolhida uma linha atual por **município + fonte + indicador + período de referência**. Uma nova importação do mesmo conjunto deverá atualizar essa linha, sem duplicá-la; períodos diferentes permanecem em linhas diferentes. A migration proposta para `indicadores_municipais` terá vínculo obrigatório com `municipios` e `fontes_dados`, código do indicador, valor, unidade, período de referência, data/hora da coleta, versão da fonte quando disponível e timestamps. Uma restrição única composta sustentará essa regra. A inclusão de uma fonte concreta em `fontes_dados` e a importação dos valores são etapas posteriores; seeders não são necessários para criar a tabela.

Essa tabela não é um histórico de todas as importações. Caso análises sejam persistidas futuramente, os valores e a procedência efetivamente usados deverão ser preservados em um registro/snapshot próprio, para que reimportações não alterem análises antigas. A versão/data da base precisará estar identificada antes de usar o indicador em uma análise, conforme a RI-010; a data da coleta local não a substitui. Persistir análises na primeira versão, política de retenção, frequência de atualização e identificação exata da versão da fonte continuam **A CONFIRMAR**. Nenhum score será tratado como validado antes da aprovação da fórmula e da cobertura dos dados mínimos.

### Decisões a confirmar

| Tema | Decisão necessária | Impacto |
| --- | --- | --- |
| Banco | Nome `rightpoint` definido; confirmar na escola a versão do MySQL/MariaDB, collation e conexão local antes de aplicar novas migrations. | Compatibilidade e execução em cada computador. |
| Área geográfica | Recorte inicial definido: 92 municípios do RJ, selecionados por busca ou mapa via código IBGE; bairros e raio ficam para depois. Verificar a edição da malha e a cobertura dos dados. | Relacionamentos, mapa e seleção de concorrentes. |
| Atividades | CNAE identifica a atividade; a primeira contagem por CNAE **principal exato** é proposta para validar com o grupo. CNAEs secundários/equivalentes continuam `A CONFIRMAR`. | Catálogo e associação com estabelecimentos. |
| Concorrentes | Proposta inicial: somente CNPJs ativos, no mesmo município e com CNAE principal exato. Validar regra e correspondência entre códigos municipais da Receita e do IBGE. | Resultado e confiabilidade da contagem, inclusive zero. |
| Histórico | Definir se análises serão persistidas na primeira versão. | Armazenamento de resultados e dados usados no cálculo. |
| Identidade e perfil | CRUD de perfil do negócio planejado; campos adicionais, necessidade de login e vínculo do perfil/análises ao usuário `A CONFIRMAR`. | Persistência, autenticação e autorização. |
| Interface | Primeira seleção por busca e clique no mapa municipal do RJ; escolher abordagem de frontend compatível com Laravel. | Implementação da consulta e do futuro CRUD. |
| Dados externos | Fontes candidatas registradas em [FONTES_DADOS](../FONTES_DADOS.md); verificar cobertura, acesso, licença, atualização e adequação de cada indicador antes de integrar. | Viabilidade dos indicadores e da concorrência. |
| Atualização de fontes | Direção escolhida: agendada, uma fonte por vez, sem chamadas externas em cada consulta. Frequência, retentativas e prazo de validade `A CONFIRMAR`. | Disponibilidade, carga e tratamento de dados defasados. |
| Score | População e cobertura CNPJ são dados mínimos propostos; fórmula, pesos e fatores opcionais precisam de validação acadêmica. Sem mínimos ou fórmula, não apresentar score validado. | Cálculo reproduzível, justificativa e aviso de insuficiência. |

Resolver cada pendência antes da etapa que depende dela. Fontes e fórmula podem permanecer pendentes durante um protótipo de consulta, desde que ele não apresente um score como validado.

## Sequência proposta

### 1. Validar escopo e ambiente

- [x] Documentar recorte RJ, matriz de fontes e fluxograma de atualização/consulta; critérios de score e concorrência ainda dependem de validação acadêmica.
- [ ] Confirmar as decisões que afetam o primeiro modelo de dados.
- [ ] Conferir e preservar o ambiente existente no computador da escola, incluindo migrations ainda não enviadas.
- [ ] Verificar versões instaladas, dependências e execução básica do Laravel.
- [ ] Verificar a configuração do banco sem expor secrets e consultar o estado das migrations.
- [ ] Registrar as decisões aprovadas na documentação pertinente.

Critério de conclusão: escopo inicial explícito e ambiente mínimo validado, com limitações registradas.

### 2. Implementar a base de dados do domínio

- [x] Versionar e aplicar no notebook do trabalho as migrations iniciais de municípios e atividades econômicas por CNAE.
- [x] Criar a migration e o model de fontes de dados; aplicar a migration no banco local da escola.
- [x] Validar e preparar em memória os 92 municípios do RJ retornados pelo IBGE no notebook da escola, sem gravar no banco.
- [x] Implementar a importação sem duplicatas em `municipios`; duas execuções reais no MySQL local em 13/10/2026 mantiveram 92 linhas, códigos distintos, IDs e datas de criação.
- [x] Criar e revisar o comando de importação das 1.332 subclasses CNAE; o banco da escola contém 1.332 códigos distintos em 14/10/2026. O usuário informou que repetiu o comando; uma consulta posterior confirmou a mesma contagem e os mesmos limites de ID, sem duplicação observável.
- [x] Criar e revisar o arquivo da migration de `indicadores_municipais`; sintaxe e SQL simulado conferidos em 14/10/2026, sem aplicar ao banco.
- [x] Criar e revisar o model `IndicadorMunicipal`; `$fillable` e os vínculos pertencentes a município e fonte foram conferidos, sem testes de persistência ainda.
- [x] Aplicar a migration de `indicadores_municipais` no banco local `rightpoint` e confirmar o estado `Ran` no lote `[3]`.
- [x] Preparar em memória os 92 valores de população de 2022 associados ao ID interno de cada município; conferência concluída no terminal do usuário em 14/10/2026, ainda sem fonte/data da coleta ou gravação.
- [x] Registrar a fonte e importar os 92 indicadores municipais de população do Censo 2022 no notebook do trabalho; consulta de leitura encontrou 92 municípios distintos, fonte vinculada e data de coleta. A rota local devolveu esses dados para Maricá em 20/10/2026.
- [ ] Conferir na interface a população após a carga e repetir a importação para observar a ausência de duplicatas no banco local.
- [ ] Revisar atributos, cardinalidades e restrições do modelo conceitual.
- [ ] Definir migrations e models ainda necessários para indicadores municipais e estabelecimentos/CNAEs, conforme o recorte aprovado; preservar os catálogos e a tabela de fontes já criados.
- [ ] Incluir relacionamentos necessários, sem converter automaticamente cada classe conceitual em tabela.
- [ ] Criar dados de demonstração identificados como fictícios, caso essa abordagem seja adotada.
- [ ] Avaliar seeders para cadastros fixos e demonstração quando houver dados aprovados; prioridade baixa definida pelo usuário em 14/10/2026, sem bloquear a integração dos indicadores reais.
- [ ] Validar integridade e relacionamentos em banco de desenvolvimento ou teste.

Critério de conclusão: estrutura reproduzível por migrations, coerente com as regras aprovadas. Avaliar dados existentes e reversibilidade antes de executar mudanças estruturais; não usar reset destrutivo como rotina.

### 3. Construir a primeira consulta

- [x] Implementar seleção de CNAE e município do RJ por busca/lista e clique na malha, vinculados ao código IBGE; clique ainda pendente de validação com a malha real.
- [x] Validar formato das entradas e consultar os indicadores já armazenados; concorrentes seguem indisponíveis até integração e cobertura CNPJ.
- [x] Apresentar estados iniciais de erro e insuficiência de dados, sem tratar ausência como zero; estado de fonte defasada ainda pendente.
- [x] Implementar CRUD de perfis local ao navegador sem login, como protótipo explícito. Propriedade/autenticação e persistência no banco continuam pendentes.
- [ ] Identificar claramente dados demonstrativos, caso utilizados.
- [ ] Validar o fluxo inteiro no navegador, inclusive troca entre as três bases cartográficas, seleção por polígono, CRUD local e responsividade. A captura headless confirmou a base escura com 92 limites, mas não cobriu interações.

Critério de conclusão: formulário → consulta → resultado funcionando. Enquanto não houver fórmula aprovada, informar que o score está indisponível.

### 4. Integrar fontes reais e implementar o score

- [ ] Validar fontes, cobertura geográfica e procedência dos dados.
- [ ] Implementar importações agendadas, sequenciais por fonte, com registro de versão/data, tratamento de falhas e sem publicação de carga parcial.
- [ ] Validar o mapeamento do município da Receita para o código IBGE e distinguir contagem zero de falta de cobertura.
- [ ] Implementar fórmula e pesos aprovados, com justificativa baseada nos fatores efetivamente usados.
- [ ] Testar cálculos, limites e insuficiência de dados.
- [ ] Implementar histórico e preservação do contexto da análise, se aprovados.

Critério de conclusão: resultado rastreável, cálculo reproduzível e limitações apresentadas ao usuário.

## Cuidados de continuidade

- Preservar mudanças locais e consultar instruções do projeto antes de editar código.
- Não assumir MySQL apenas porque o repositório está dentro do XAMPP.
- Registrar a configuração de desenvolvimento aprovada (`rightpoint`, host, porta e usuário), mas não copiar a chave da aplicação nem futuras senhas ou tokens para a documentação.
- Não introduzir IA, RAG ou serviços externos sem necessidade definida pelo escopo.
- Atualizar este plano ao concluir etapas, registrando verificações realmente executadas e pendências remanescentes.

## Ponto de retomada

Na próxima sessão ou em outro computador, começar pela conferência do Git, do Laravel e do banco local conforme o [STATUS.md](../STATUS.md) e o [procedimento para múltiplos computadores](../procedimentos/ambiente-multiplos-computadores.md). Os 92 municípios e 1.332 CNAEs foram observados no banco da escola e no notebook do trabalho; a população foi importada e consultada no notebook do trabalho em 20/10/2026, mas **não** é transferida pelo Git. Cada máquina precisa conferir conexão, migrations e dados antes de repetir as cargas localmente. A rota `/cnaes` está ligada ao formulário; capturas confirmaram seleção de atividade, consulta de Maricá e um perfil salvo. Faltam a conferência da população na interface atualizada, repetição da carga para observar idempotência, testes de edição/exclusão, troca de bases e responsividade. Perfis locais não acompanham o Git. O próximo trabalho de dados é investigar a fonte CNPJ e a correspondência de códigos municipais. As propostas da [matriz](../arquitetura/matriz-dados-diagnostico.md) ainda dependem de validação com o grupo/professora; o score permanece indisponível.
