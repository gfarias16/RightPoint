# Fontes de dados candidatas — RightPoint

Registro do grupo, atualizado em 27/10/2026. Uma fonte listada aqui não está automaticamente integrada nem aprovada para o cálculo do score. Antes de usá-la, conferir acesso, licença, cobertura geográfica, período, unidade, atualização e qualidade dos dados. A [matriz de dados e o fluxograma](./arquitetura/matriz-dados-diagnostico.md) organizam os critérios **propostos** para a primeira versão, limitada aos 92 municípios do RJ.

| Fonte candidata | Dados pretendidos | Possível uso | Estado |
| --- | --- | --- | --- |
| CNPJ / Receita Federal e APIs derivadas | CNPJ, CNAE, situação, endereço e abertura | Identificação de estabelecimentos e concorrência; sobrevivência/mortalidade exige critério e dados históricos apropriados | Minha Receita: páginas reais pequenas conferidas; paginação de 1 a 3 páginas e repetição entre páginas testadas com HTTP simulado; cobertura e persistência pendentes |
| IBGE — Localidades e Censo/SIDRA | Municípios, população, renda, densidade e perfil etário, conforme tabela disponível | Delimitação municipal e indicadores socioeconômicos | Localidades e duas consultas de população testadas abaixo; os demais indicadores não foram testados |
| ANP | Preços de combustíveis por região | Possível indicador indireto, cuja relação com fluxo/movimento ainda precisa ser justificada | A CONFIRMAR |
| INMET | Temperatura, chuva e histórico climático | Avaliar influência climática conforme a atividade | A CONFIRMAR |
| Portal da Transparência / SICONFI | Obras e investimentos públicos | Investigar investimento regional; não presumir valorização como consequência | A CONFIRMAR |
| OpenStreetMap / Mapbox | Coordenadas, ruas, bairros, pontos de interesse e geocodificação | Consulta espacial e mapa | A CONFIRMAR |

## Referências oficiais identificadas em 05/10/2026

Estas referências mostram caminhos possíveis de obtenção de dados. Seus arquivos, campos, licença de reutilização, cobertura e adequação ao RightPoint ainda precisam ser avaliados antes de qualquer importação.

- **CNAE:** [busca oficial da CONCLA/IBGE](https://concla.ibge.gov.br/busca-online-cnae.html), [catálogo para download](https://cnae.ibge.gov.br/classificacoes/download-concla/8265-download) e [documentação da API CNAE v2 do IBGE](https://servicodados.ibge.gov.br/api/docs/CNAE?versao=2), com o recurso `GET /api/v2/cnae/subclasses`. No terminal do usuário, em 14/10/2026, a consulta retornou 1.332 itens; o primeiro tinha `id` textual de sete dígitos e `descricao`. O total coincide com a [publicação oficial da CNAE-Subclasses 2.3](https://www.ibge.gov.br/comunicados/23506-ibge-disponibiliza-versao-2-3-das-subclasses-da-classificacao-nacional-de-atividades-economicas.html), mas a versão da resposta ainda precisa ser confirmada. A saída do PowerShell apresentou acentos corrompidos; validar a codificação no Laravel antes de gravar. A tentativa de conexão deste ambiente de ferramentas falhou. O [leiaute dos dados abertos de CNPJ da Receita Federal](https://www.gov.br/receitafederal/dados/cnpj-metadados.pdf) também descreve um arquivo de códigos e descrições de CNAE; verificar a compatibilidade antes de relacionar as bases.

  **Validação posterior na escola (14/10/2026):** com um pacote CA local que inclui o intermediário faltante, `php artisan ibge:verificar-cnaes` validou os 1.332 registros e exibiu corretamente o primeiro nome com acentos (`RODOVIÁRIO`). Isso resolveu a dúvida de codificação na leitura feita pelo Laravel nesta máquina, mas não confirmou sozinho a versão de toda a resposta. Posteriormente, o usuário criou `ibge:importar-cnaes`; uma consulta de leitura encontrou 1.332 linhas e 1.332 códigos distintos em `atividades_economicas` no banco da escola, com códigos de sete dígitos e nomes preenchidos. O usuário informou que repetiu o comando; uma nova consulta encontrou os mesmos totais e limites de ID, sem duplicação observável. A saída das execuções não foi compartilhada. O comando mapeia `id` → `codigo_cnae` e `descricao` → `nome`, e deixa a coluna opcional `descricao` sem preenchimento. A versão do catálogo continua a confirmar. Consulte o [procedimento de HTTPS](./procedimentos/ambiente-multiplos-computadores.md#acesso-https-ao-ibge-com-cadeia-incompleta).
- **Estabelecimentos/CNPJ:** [catálogo de dados abertos da Receita Federal](https://www.gov.br/receitafederal/pt-br/acesso-a-informacao/dados-abertos/cadastros) e [leiaute de CNPJ](https://www.gov.br/receitafederal/dados/cnpj-metadados.pdf). O arquivo efetivo, o método de obtenção e o mapeamento do município da Receita para o código IBGE seguem A CONFIRMAR. A Receita [iniciou a emissão de CNPJs alfanuméricos em 2026](https://www.gov.br/receitafederal/pt-br/assuntos/noticias/2026/julho/receita-federal-gera-o-primeiro-cnpj-em-formato-alfanumerico): armazenar o identificador como texto de 14 posições, quando essa tabela for criada.
- **ANP:** [série histórica de preços de combustíveis em CSV](https://www.gov.br/anp/pt-br/centrais-de-conteudo/dados-abertos/serie-historica-de-precos-de-combustiveis). Preço pesquisado não equivale diretamente a fluxo de pessoas nem a potencial comercial.
- **INMET:** [BDMEP e dados históricos](https://portal.inmet.gov.br/servicos/bdmep-dados-historicos). As medições são de estações; decidir como relacioná-las a municípios antes de usar um indicador municipal.
- **Investimento público:** [API de dados contábeis do SICONFI](https://www.tesourotransparente.gov.br/consultas/consultas-siconfi/siconfi-api-de-dados-abertos) e [API de obras do Obrasgov](https://www.gov.br/obrasgov/pt-br/ferramentas-de-gestao-e-transparencia/api-de-dados-abertos-obrasgov.br). São conjuntos distintos; selecionar uma medida concreta antes de criar indicador.
- **Geocodificação:** [política de uso do Nominatim público, ligado ao OpenStreetMap](https://operations.osmfoundation.org/policies/nominatim/). O recorte inicial por município não exige geocodificação nem Mapbox.
- **Mapa municipal:** [Malha Municipal do IBGE](https://www.ibge.gov.br/geociencias/organizacao-do-territorio/malhas-territoriais/15774-malhas.html) como candidata para os limites dos municípios. Edição, arquivo do RJ e forma de uso no navegador ainda precisam ser escolhidos e testados.

A migration `create_fonte_dados_table` cria `fontes_dados` para registrar a origem dos dados efetivamente usados. Ela não importa as fontes candidatas nem substitui este documento de pesquisa.

## 1. IBGE — municípios do estado do Rio de Janeiro

Endpoint fornecido pelo usuário:

https://servicodados.ibge.gov.br/api/v1/localidades/estados/33/municipios?

Consulta de leitura realizada em 05/10/2026: retornou 92 municípios. Cada item inclui `id` (código IBGE), `nome` e dados da divisão territorial; a sigla `RJ` aparece na estrutura aninhada da UF. O município do Rio de Janeiro tem código `3304557`. Esta resposta **não** traz população nem coordenadas. O `?` final da URL não adiciona nenhum parâmetro.

Mapeamento implementado para a tabela `municipios`: `id` da API → `codigo_ibge` (texto de sete caracteres); `nome` → `nome`; `microrregiao.mesorregiao.UF.sigla` → `uf`. O `id` interno da tabela Laravel é diferente do código IBGE.

Em 13/10/2026, `php artisan ibge:importar-municipios-rj` gravou os 92 municípios no MySQL/MariaDB local do XAMPP. O comando valida a lista inteira e usa `upsert` dentro de uma transação. Duas execuções reais mantiveram 92 códigos distintos, UF `RJ`, IDs e datas de criação. Nome, UF e `updated_at` podem ser atualizados em execuções posteriores. A importação é manual; agendamento, periodicidade e registro da origem na tabela `fontes_dados` continuam pendentes.

## 2. IBGE SIDRA — população residente do Rio de Janeiro no Censo 2022

Endpoint fornecido pelo usuário:

https://apisidra.ibge.gov.br/values/t/4714/n6/3304557/v/93/p/2022

Consulta de leitura realizada em 05/10/2026: a primeira posição do array é um cabeçalho que explica os campos. O registro seguinte contém `D1C=3304557` (código do município), `D2N=População residente`, `D3C=2022`, `MN=Pessoas` e `V=6211223`. O valor `V` vem como texto; validar antes de convertê-lo em número.

Esta URL pede **apenas população residente de 2022 para um município**. Ela não entrega renda, perfil etário, dados por bairro ou concorrentes. Como o recorte é municipal, não se deve atribuir esse total a um bairro ou a um raio específico.

O código IBGE `3304557` permite associar a resposta do SIDRA ao município da API de Localidades, sem depender do nome escrito da cidade.

Em 14/10/2026, o usuário executou um segundo experimento de leitura, sem gravar dados, pela API de Agregados v3 do IBGE: `https://servicodados.ibge.gov.br/api/v3/agregados/4714/periodos/2022/variaveis/93?localidades=N6%5BN3%5B33%5D%5D`. O comando `ibge:verificar-populacao-rj` exibiu **92 séries**; o primeiro registro foi Angra dos Reis (`3300100`), com `serie.2022 = "167434"`. Depois, o usuário acrescentou validações dos códigos, do nível municipal, do valor numérico de 2022, das duplicatas e da correspondência com os 92 municípios do banco; a nova execução terminou com a mensagem de conferência bem-sucedida. O código foi revisado e passou em `php -l`. A execução no ambiente de ferramentas falhou por indisponibilidade de rede; as saídas bem-sucedidas foram compartilhadas pelo usuário.

Uma captura enviada pelo usuário do [endpoint oficial de metadados do agregado 4714](https://servicodados.ibge.gov.br/api/v3/agregados/4714/metadados) confirmou a pesquisa `Censo Demográfico`, nível municipal `N6` e a variável 93 com nome **População residente** e unidade **Pessoas**. A periodicidade mostrada é anual, com início e fim em 2022; este indicador deve ser rotulado como população do **Censo 2022**, não como população atual. Em 20/10/2026, no notebook do trabalho, o usuário executou o verificador e o importador: a consulta posterior encontrou **92 registros em 92 municípios distintos**, fonte `ibge-censo-2022-populacao` vinculada e data de coleta preenchida. A rota local de consulta retornou o valor e a procedência para Maricá. `versao_fonte` identifica a edição/tabela/variável usada, não uma revisão oficial comprovada; a carga local não acompanha o Git.

## 3. Receita Federal — CNPJ

### APIs candidatas para simplificar a obtenção

Em 20/10/2026, o usuário questionou a necessidade de arquivos grandes e pediu alternativa por API. A próxima avaliação deve priorizar busca por município e CNAE, antes de definir um fornecedor. O leitor CSV continua como exercício concluído e alternativa; nenhum ZIP foi baixado.

- **Minha Receita:** a [documentação de busca paginada](https://docs.minhareceita.org/como-usar/#busca-paginada) aceita `uf`, `municipio` (IBGE ou SIAFI), `cnae_fiscal`, `limit` e `cursor`. O parâmetro `cnae` também inclui atividades secundárias; para o critério proposto de atividade principal exata, avaliar `cnae_fiscal`. A [apresentação do serviço](https://docs.minhareceita.org/) informa acesso gratuito sem chave; a busca paginada é experimental e pode responder HTTP 429. Uma consulta real com `uf=RJ`, `municipio=3302700`, `cnae_fiscal=5611201` e `limit=2` retornou 429, sem resultados empresariais. Não foi repetida automaticamente. Filtro de situação ativa, paginação completa, atualização e cobertura continuam a validar; a resposta documentada usa `data` e `cursor`.
- **Casa dos Dados:** a [pesquisa avançada v5](https://docs.casadosdados.com.br/pesquisa-avan%C3%A7ada-de-empresas-16579062e0) documenta UF, município, CNAE principal, situação cadastral e paginação. As [informações de acesso](https://docs.casadosdados.com.br/informa%C3%A7%C3%B5es-b%C3%A1sicas-1278846m0) exigem chave e saldo para pesquisa. Nenhuma conta, contratação ou requisição autenticada foi feita; custo e condições do projeto ainda precisam ser avaliados.
- **BrasilAPI:** o [endpoint documentado de CNPJ](https://github.com/BrasilAPI/BrasilAPI/blob/main/pages/docs/doc/cnpj.json) consulta um identificador conhecido. Esse recurso sozinho não encontra todos os estabelecimentos de uma cidade/atividade.

Em 21/10/2026, no notebook do trabalho, o usuário criou e executou `cnpj:verificar-busca 3302700 5611201`: a saída mostrou **2 registros nesta página** e indicação de próxima página, sem gravação. A revisão confirmou timeout, tratamento de HTTP 429 e checagem da lista `data`; sintaxe e rejeição de argumentos inválidos passaram. O agente não repetiu a requisição externa. Nessa primeira execução, os campos individuais ainda não eram conferidos; a validação foi acrescentada depois, conforme o registro seguinte. A consulta a serviços derivados não implica dados em tempo real da Receita nem dispensa o registro de procedência.

Conferência adicional em 21/10/2026: o usuário validou os campos de 2 registros da página real de Maricá/CNAE `5611201`; ambos corresponderam a UF, município IBGE e CNAE principal. Um registro veio ativo (situação 2) e outro inapto (situação 4, conforme o [leiaute oficial](https://www.gov.br/receitafederal/dados/cnpj-metadados.pdf)). Depois, implementou `--cursor`; seu envio foi conferido por HTTP simulado. Após corrigir a montagem da tabela, a primeira página mostrou dois CNPJs diferentes. O agente consultou a segunda página com acesso à rede permitido: outros dois CNPJs, distintos dos anteriores, e novo cursor, sem gravação. Quatro testes simulados da classe atual passaram para linhas, vazio, repetido na página e zero inicial do CNAE. A checagem atual não valida formato/dígitos verificadores do CNPJ nem guarda identificadores entre execuções. O [exercício de paginação limitada](./planos/exercicio-paginacao-cnpj.md) prepara essa evolução, mas ainda não foi aplicado à classe. Cobertura completa, procedência, edição/data da base e persistência continuam pendentes; as páginas não representam o total de concorrentes.

Estado mais recente da busca, em 21/10/2026: o agente implementou o laço autorizado de até 3 páginas, mantendo filtros, CNPJs e cursores vistos entre páginas. Onze testes persistentes do comando com HTTP simulado passaram; a confirmação do novo laço contra o serviço externo permanece pendente. O resultado continua sendo amostra sem gravação, procedência persistida ou contagem validada. O guia acima é uma referência didática anterior, não instrução para sobrescrever a classe atual.

**Teste real posterior, em 21/10/2026:** o usuário executou a nova versão com `--paginas=2` para Maricá (`3302700`) e CNAE principal `5611201`. A primeira página trouxe 2 registros e cursor; um estava ativo e outro tinha situação 4. A segunda requisição recebeu HTTP 429, e o comando interrompeu a busca sem retentativa nem gravação. Isso confirma o tratamento do limite observado nessa execução, mas não valida uma segunda página automática completa, a cobertura da fonte ou qualquer total de concorrentes. Não repetir imediatamente a chamada apenas para obter uma contagem; antes da persistência, avaliar limites de acesso, atualização e procedência da fonte.

**Avaliação de viabilidade em 21/10/2026:** a [documentação da busca paginada](https://docs.minhareceita.org/como-usar/#busca-paginada) a descreve como experimental, sujeita a mudanças e a HTTP 429 por **instância do servidor**, não por cliente/IP. A [apresentação do serviço](https://docs.minhareceita.org/) não promete disponibilidade, e o [FAQ](https://docs.minhareceita.org/faq/) informa atualização manual. O endpoint [de data da fonte](https://minhareceita.org/updated) retornou `2026-10` nesta consulta; isso identifica a extração informada pelo serviço, sem comprovar que todos os estabelecimentos de cada filtro estão acessíveis. Os filtros documentados permitem município IBGE, UF e `cnae_fiscal` (atividade principal); não há filtro de situação cadastral na lista, portanto a regra de contar apenas ativos dependeria de percorrer e validar **todas** as páginas. Com o 429 na segunda página, essa condição não foi satisfeita. **Conclusão provisória:** manter a API como fonte de exploração e testes, sem contagem de concorrentes nem importação definitiva. Para avançar, comparar uma extração completa e datada dos [arquivos oficiais da Receita](https://www.gov.br/receitafederal/pt-br/acesso-a-informacao/dados-abertos/cadastros/cnpj) ou outra fonte com cobertura e limites documentados; só então aprovar a origem e a estrutura de persistência. Essa recomendação é uma inferência dos limites documentados e da execução observada, não uma afirmação de que a API omitiu registros específicos.

### Comparação para a contagem de concorrentes

Comparação documental feita pelo agente em 21/10/2026 para a proposta inicial de **município, CNAE principal exato e situação ativa**; o critério ainda depende de validação acadêmica. Nenhuma dessas alternativas foi integrada ou teve cobertura comprovada no RightPoint.

| Fonte | Vantagem para o recorte inicial | Condição antes de usar no diagnóstico |
| --- | --- | --- |
| [Mapa de Empresas](https://www.gov.br/empresas-e-negocios/pt-br/mapa-de-empresas/painel-mapa-de-empresas) e [manual de extração](https://www.gov.br/empresas-e-negocios/pt-br/mapa-de-empresas/arquivos/MANUALMAPADEEMPRESASv2.pdf/@@display-file/file) | Painel público com totais de CNPJs ativos por atividade e município, atualização mensal declarada e exportação manual dos filtros. Pode fornecer um total municipal sem baixar os arquivos nacionais. | Confirmar no painel se o filtro aceita a **subclasse CNAE exata de sete dígitos** e se o total/exportação respeita simultaneamente município, atividade e situação ativa. Não há lista individual de CNPJs nem API de integração confirmada nesta pesquisa. Registrar edição, data e filtros; não inferir concorrentes por raio. |
| [Arquivos abertos da Receita](https://www.gov.br/receitafederal/pt-br/acesso-a-informacao/dados-abertos/cadastros/cnpj) e [leiaute](https://www.gov.br/receitafederal/dados/cnpj-metadados.pdf) | Dados por estabelecimento com situação, CNAE principal, UF e código municipal; permitem identificar CNPJs e reproduzir a contagem a partir de uma edição datada. | Processar todas as partes da mesma edição para alegar cobertura; a edição `2026-10` examinada soma cerca de 5,42 GB compactados só em estabelecimentos. Conferir o vínculo entre código municipal da Receita e código IBGE; não gravar o conjunto nacional inteiro sem necessidade. |
| [Casa dos Dados, pesquisa v5](https://docs.casadosdados.com.br/pesquisa-avan%C3%A7ada-de-empresas-16579062e0) | API documenta filtros de CNAE principal, situação `ATIVA`, UF e município; resposta inclui `total` e CNPJs. | [Chave e saldo](https://docs.casadosdados.com.br/informa%C3%A7%C3%B5es-b%C3%A1sicas-1278846m0) são exigidos. Filtro municipal documentado por nome, com código IBGE na resposta: conferir correspondência, custo real, edição e completude antes de depender do `total`. Nenhum acesso autenticado foi feito. |
| [Minha Receita, busca paginada](https://docs.minhareceita.org/como-usar/#busca-paginada) | Busca gratuita por município IBGE e CNAE fiscal, útil para explorar registros individuais. | Busca experimental; segunda página real recebeu 429 e não há total completo validado. Não usá-la para afirmar zero, total de concorrentes ou score neste estágio. |

**Direção provisória:** testar manualmente no Mapa de Empresas Maricá (`3302700`), CNAE `5611201` e CNPJs ativos, anotando se o código de sete dígitos e a exportação são possíveis. Se o painel não sustentar esse recorte ou se for necessária a identidade de cada estabelecimento, avaliar o processamento filtrado dos arquivos oficiais; a API paga fica condicionada à aceitação de custo e à validação da cobertura. Um total agregado não substitui a lista de concorrentes prevista para etapas posteriores. Nenhuma fonte de estabelecimentos foi escolhida nem houve nova migration.

#### Checklist de validação do Mapa de Empresas — 22/10/2026

A [página oficial](https://www.gov.br/empresas-e-negocios/pt-br/mapa-de-empresas/painel-mapa-de-empresas) consultada nesta data informa setembro/2026 para Registro de Empresas. O [manual de junho/2026](https://www.gov.br/empresas-e-negocios/pt-br/mapa-de-empresas/arquivos/MANUALMAPADEEMPRESASv2.pdf/@@display-file/file) descreve CNPJs ativos e exportação de objetos por “Baixar como...” → “Dados”; tabelas detalhadas de aberturas/fechamentos não devem ser confundidas com o estoque ativo. A interação com os filtros ainda precisa de evidência.

- Selecionar RJ, Maricá (código de referência IBGE `3302700`) e atividade `5611201`/`5611-2/01`, sem aceitar categoria mais ampla como equivalente.
- Conferir se o filtro considera CNAE principal ou inclui secundários; se o painel não explicar, marcar **A CONFIRMAR**.
- Identificar a medida de CNPJs ativos, os filtros efetivos e o período; não usar número de aberturas no lugar de estabelecimentos ativos.
- Capturar filtros/total/período e tentar exportar os dados do objeto correspondente. Conferir o arquivo antes de definir um importador; exportação manual não comprova API pública.
- Resultado pendente: ainda não há total confiável, arquivo aprovado ou escolha definitiva da fonte. Um total municipal não identifica estabelecimentos individuais nem concorrentes por raio.

### OpenCNPJ — avaliação autorizada em 27/10/2026

**Importador em lote entregue em 28/10/2026:** `cnpj:importar-contagens-rj <arquivo>` simula; `--gravar` habilita transação de fonte/coletas. Reutiliza `ValidadorContagensRj`, exige hash LF revisado e consulta UTC `2026-10-28 20:01:33` configurados em `contagem_cnpj_rj.php`. Não associa arquivo diferente ao job, inventa competência, sobrescreve divergência ou apaga piloto. 14 testes novos, suíte completa 85/447 aprovada em testes; simulação real 92/39.423 preservou banco/arquivo. **Primeira escrita do lote no MySQL real ainda pendente**; sem carga ou BigQuery pelo agente. Retomada/transporte do JSON no procedimento de ambiente.

**Verificador em lote implementado em 28/10/2026:** `cnpj:verificar-contagens-rj <arquivo>` aprovou os 92 grupos/soma municipal 39.423 no arquivo real, sem mudar arquivo ou banco. Configuração ampliada para 92 pares explícitos Receita↔IBGE; exceção restrita Parati/Paraty, sem vínculo por semelhança. Contrato, filtros, contagens, códigos, nomes e catálogos são conferidos antes de qualquer sucesso. 41 novos testes; suíte completa com 71 testes/342 asserções aprovada. Não importa dados, autentica arquivo, confirma cobertura ou fornece score. Carga em lote ainda pendente; não repetir BigQuery.

**Tabela reconferida nas capturas de 28/10/2026:** `opencnpj-bigquery.public.receita`, us-east1, última modificação em 14/10/2026 06:44:25 UTC−3 (`09:44:25` UTC), 73.797.761 linhas, 44,82 GB lógicos/5,09 GB físicos exibidos. Descrição vazia; não há competência/identificador de edição comprovados. Guardar os metadados como evidência e manter período/versão NULL na futura carga; não usar mês da modificação ou do job como competência. Job e arquivo já conferidos abaixo; próxima etapa é validação local em lote, sem nova aquisição paga.

**Job do lote RJ conferido em 28/10/2026:** capturas confirmam SQL preparado, região us-east1, criação 17:01:32 UTC−3, início 17:01:33 UTC−3 (`20:01:33` UTC), término 17:01:34 UTC−3 e duração 1 s 36 ms. Processados/faturados 3,81 GB nas unidades arredondadas da interface; não comprova valor monetário. Prioridade interativa, SQL legado false, destino temporário; cache/teto não visíveis. E-mail e identificadores privados omitidos. Detalhes e versão/período da tabela-fonte ainda pendentes; não usar a data do job como competência. Nenhuma nova consulta ou importação pelo agente.

**Lote RJ recebido em 28/10/2026:** usuário informou execução e salvou JSON agregado; conferência local do agente encontrou 92 grupos/códigos Receita distintos, todos RJ/5611201/Ativa, soma municipal 39.423, ausentes 0 e Maricá 620. Arquivo/hash e conciliação exploratória no [procedimento](./procedimentos/comparacao-cnpj-municipios-rj.md#arquivo-recebido-e-conferido). 91 nomes têm candidato IBGE único após normalização; exceção Parati/Paraty documentada para revisão, sem novos vínculos aplicados. Sem importação do lote, metadados do job/publicação ainda pendentes e nenhuma repetição da consulta pelo agente. Não prova cobertura real nem unicidade de CNPJ entre municípios.

**Comparação municipal preparada em 28/10/2026:** [SQL/procedimento](./procedimentos/comparacao-cnpj-municipios-rj.md) agregam o mesmo CNAE 5611201/Ativa no RJ, preservando código/nome Receita e controles. Sem execução, estimativa ou resultado recebidos. Usuário deverá fornecer metadados atuais da tabela e estimativa/teto antes de nova autorização de despesa; publicação do serviço `/info` não identifica automaticamente a edição BigQuery nem a coleta antiga. Somente Maricá tem vínculo IBGE conferido; nenhum importador em lote criado.

**Estado atual em 28/10/2026:** usuário importou o agregado com `cnpj:importar-contagem --gravar` e repetiu sem duplicação. SELECT do agente confirmou uma coleta de Maricá/IBGE `3302700`/CNAE `5611201`, fonte `opencnpj-receita`, controles `620/620/0` e consulta `2026-10-28 15:08:25` UTC. Período/versão NULL; nenhum cadastro individual importado ou score calculado. Com autorização posterior, agente integrou a leitura à consulta demonstrativa: HTTP real retornou 620 com fonte/data/avisos, sem chamadas externas. Tela preparada para exibir procedência e indisponibilidade, sem validação visual por bloqueio de permissão do navegador. Os parágrafos seguintes preservam as etapas anteriores e suas pendências à época.

**Arquivo e job conferidos em 28/10/2026:** agregado salvo e validado localmente como lista JSON com um objeto e contagens textuais `620/620/0`; arquivo de 90 bytes ignorado pelo Git. Prints do job confirmam o SQL, região `us-east1`, consulta em 28/10/2026 12:08:25 UTC−3, 387 ms e **2,96 GB processados/faturados** conforme arredondamento da interface, sem preço monetário ou campo explícito de cache. E-mail/identificadores privados não registrados. Referência atual da tabela ainda pendente; data da consulta não é competência da fonte. Ver [procedimento e hash do arquivo](./procedimentos/contagem-cnpj-piloto.md#arquivo-e-job-conferidos). Nada importado no MySQL.

**Contagem posterior executada pelo usuário em 28/10/2026:** no SQL agregado do [piloto](./procedimentos/contagem-cnpj-piloto.md), sem `LIMIT`, a captura retornou **620 linhas, 620 CNPJs distintos e 0 ausentes** para RJ/Receita `5853`/CNAE principal `5611201`/`Ativa`. Teto de 3 GiB visível. Sem repetição exata identificada ou identificador nulo/vazio no recorte; não é validação individual dos identificadores ou prova independente de cobertura real. Ainda faltam arquivo agregado, metadados atuais da tabela/job e persistência; nenhuma importação. A autorização única já foi utilizada e não permite nova execução automática.

**Próxima etapa preparada em 28/10/2026:** [contagem agregada do piloto](./procedimentos/contagem-cnpj-piloto.md), sem `LIMIT`, com controles de identificadores ausentes/distintos. Preparação autorizada pelo usuário; SQL ainda sem validação/estimativa no editor, execução ou resultado. Não é total já conferido nem autorização para outra despesa. A [proposta H3](./planos/analise-territorial-h3.md) é evolução territorial separada e exige coordenadas e dados intramunicipais ainda não integrados; não muda o contrato do piloto municipal atual.

**Avanço local posterior na escola:** com autorização do usuário, o agente registrou em `Right_Point/config/municipios_receita.php` somente a correspondência já conferida `RJ:5853` → IBGE `3302700`. `cnpj:verificar-municipio 5853 RJ` consulta o catálogo local por IBGE + UF e encontrou Maricá no MySQL; não usa ID fixo nem grava dados. Seis testes específicos com consultas simuladas passaram (29 asserções). Não houve nova consulta ao BigQuery, validação independente dos demais municípios ou importação. O par tem a evidência do teste municipal abaixo; o mapeamento completo, cobertura e procedência continuam pendentes.

**Situação final desta sessão:** usuário executou duas consultas autorizadas no BigQuery e baixou uma amostra de 10 registros. O comando local `cnpj:verificar-amostra {arquivo?}` foi implementado e conferiu campos/filtros/duplicatas da lista JSON; também rejeitou duplicata fictícia. Nada importado no MySQL e nenhum total de concorrentes validado. Testes adicionais foram adiados pelo usuário para preparar a troca de computador. As amostras são ignoradas pelo Git; ver [procedimento de transporte](./procedimentos/ambiente-multiplos-computadores.md#amostras-cnpj-na-retomada-da-escola--27102026). Os registros seguintes preservam a evolução da investigação: frases de etapas iniciais como “não executado” ou “a confirmar” não anulam os resultados posteriores. Custos efetivos dos jobs, cobertura e condições dos dados continuam pendentes; as autorizações anteriores não abrangem novas consultas.

O usuário autorizou avaliar **[opencnpj.org](https://opencnpj.org/)**, associado ao [repositório Hitmasu/OpenCNPJ](https://github.com/Hitmasu/OpenCNPJ), sem aprovar ainda importação ou integração definitiva. Não confundir com serviços homônimos em outros domínios. Foram lidos documentação, contrato OpenAPI, JSON Schema e metadados públicos; nenhum registro empresarial foi consultado nem ZIP baixado nesta avaliação.

#### Publicação observada, não cobertura local comprovada

Valores lidos diretamente de [GET /info](https://api.opencnpj.org/info), no dataset `receita`:

| Informação | Valor observado |
| --- | --- |
| Identificador da publicação | `932348faccac622c` |
| `updated_at` | `2026-10-14T09:48:02.8167836Z` |
| `record_count` declarado pelo serviço | 73.797.761 |
| ZIP nacional | `https://file.opencnpj.org/releases/receita/data.zip` |
| Tamanho compactado | 14.390.081.139 bytes: 14,39 GB decimais / 13,40 GiB |
| MD5 declarado | `24235cf2f7fcd087d347aba3d430456f` |
| Formato informado em `/info` | `ndjson+binary-index`; distribuição por prefixo de CNPJ, não por UF |

Uma requisição **HEAD**, sem corpo, retornou HTTP 200, `Content-Type: application/zip` e o mesmo `Content-Length`. Isso confirma disponibilidade e tamanho naquele momento, não integridade do conteúdo: o checksum não foi calculado e os membros do ZIP não foram inspecionados. O espaço descompactado ainda é **A CONFIRMAR**. O timestamp de publicação não comprova a competência da extração original da Receita nem atualização individual de cada CNPJ. A URL do ZIP pode mudar de conteúdo; guardar identificador, metadados e hash ao obter uma carga efetiva.

#### Adequação ao RightPoint

- O [JSON Schema público](https://api.opencnpj.org/schema) documenta `cnpj` textual de 14 caracteres (inclusive alfanumérico), `cnae_principal` textual, `situacao_cadastral`, `uf`, `municipio`, nome e endereço. O exemplo de situação ativa é `Ativa`; não reutilizar sem adaptação a comparação numérica `2` da Minha Receita.
- O esquema consultado traz **nome do município**, sem campo explícito de código IBGE. Precisaremos mapear UF + nome ao catálogo local, rejeitando ambiguidades e municípios não reconhecidos; não associar por aproximação silenciosa. Confirmar o mesmo contrato nos arquivos antes de implementar o leitor.
- O [OpenAPI publicado](https://github.com/Hitmasu/OpenCNPJ/blob/main/src/Page/assets/openapi.json) lista consulta `/{cnpj}`, metadados `/info` e esquema `/schema`; não documenta descoberta por UF/CNAE. Selecionar `datasets=receita` escolhe uma fonte de dados, não um estado ou atividade.
- O catálogo consultado publica um ZIP Receita nacional; **não foi identificado download separado do RJ**. Filtrar localmente após obter o ZIP continua exigindo transferir o volume nacional. Não carregar o arquivo inteiro em memória.
- O projeto indica a tabela [BigQuery `opencnpj-bigquery.public.receita`](https://github.com/Hitmasu/OpenCNPJ/blob/main/src/Page/src/data/bigquery.ts). É candidata a extrair apenas as colunas e linhas do RJ, mas acesso efetivo, esquema da tabela, edição, bytes processados, custos/limites e exportação ainda não foram validados. Nenhuma conta/projeto foi criado, credencial utilizada ou consulta BigQuery executada.
- O [código é MIT](https://github.com/Hitmasu/OpenCNPJ/blob/main/LICENSE); essa licença de software não comprova automaticamente os termos de reutilização dos dados de origem. Confirmar atribuição e condições dos arquivos antes da importação. Evitar coletar QSA, CPF, e-mail e telefone para o critério inicial de concorrência.

**Conclusão e próximo passo:** o contrato tem campos pertinentes, mas a carga nacional direta de 14,39 GB não está aprovada para os notebooks. Primeiro avaliar se é possível obter um recorte pequeno pelo BigQuery, conferindo acesso e estimativa de processamento antes de qualquer execução potencialmente paga. Se não for viável, discutir outra forma de aquisição; não trocar a fonte nem baixar a base inteira automaticamente. Depois de obter uma amostra autorizada, validar formato/codificação, vínculo municipal, atividade principal, situação e procedência. Amostra não comprova cobertura dos 92 municípios. Migration, leitor/importador, persistência e contagem de concorrentes continuam pendentes.

#### BigQuery — verificação de acesso em 27/10/2026

- **Atualização posterior do usuário:** informou upgrade para cobrança por uso e mostrou a página de orçamentos sem orçamento listado. Não presumir mais sandbox sem faturamento. Antes de continuar, orientar orçamento de alertas no projeto, teto de bytes por consulta sob demanda e validação/estimativa do SQL. A autorização para investigar não é autorização para gerar despesas. Não houve acesso do agente à conta nem confirmação de custos atuais.
- A tentativa anônima de `GET https://bigquery.googleapis.com/bigquery/v2/projects/opencnpj-bigquery/datasets/public/tables/receita` retornou **HTTP 401**, sem metadados. Essa operação é somente leitura de estrutura ([tables.get](https://docs.cloud.google.com/bigquery/docs/reference/rest/v2/tables/get)), não uma consulta SQL. O retorno não demonstra inexistência da tabela nem confirma as permissões de um usuário autenticado; não foram feitas tentativas com credenciais.
- O [sandbox oficial](https://docs.cloud.google.com/bigquery/docs/sandbox) permite avaliação sem cartão nem conta de faturamento, mediante conta Google e projeto. Isso é uma possibilidade de acesso, não confirmação de gratuidade de qualquer operação nem de acesso à tabela do OpenCNPJ. Nenhum projeto foi criado ou faturamento ativado pelo agente.
- **Próximo passo do usuário:** abrir `https://console.cloud.google.com/bigquery`, fazer login por conta própria e informar se já há projeto em sandbox/sem faturamento. Se aparecer pedido de cartão, upgrade ou ativação de cobrança, não avançar. Não enviar senha, token ou chave ao agente. Depois conferir no Explorador se o projeto externo `opencnpj-bigquery`, dataset `public`, tabela `receita` pode ser aberto; enviar os campos do esquema e os detalhes de atualização/localização, omitindo dados da conta. Não executar SQL ainda. Sem projeto existente, alinhar a criação de sandbox antes de continuar.
- O [dry run](https://docs.cloud.google.com/bigquery/docs/running-queries#dry-run) valida SQL e estima processamento sem executar a consulta e sem cobrança pelo dry run. Ainda **não foi possível realizar um dry run**: faltam acesso autenticado autorizado e confirmação do esquema real. Só então preparar SQL com as colunas mínimas e filtros do RJ e revisar a estimativa antes de executar.
- O [controle de custos](https://docs.cloud.google.com/bigquery/docs/best-practices-costs) recomenda `maximum bytes billed` no modelo sob demanda. `LIMIT 10` por si só não é proteção de custo: em tabelas não clusterizadas, pode continuar lendo todos os bytes das colunas consultadas. Não inferir bytes processados a partir do tamanho do ZIP ou do número de linhas retornadas.
- [Orçamentos de alertas](https://docs.cloud.google.com/billing/docs/how-to/budgets) ajudam a monitorar, mas não são um bloqueio de gastos do BigQuery. Os [spend caps](https://docs.cloud.google.com/billing/docs/how-to/budgets-spend-caps) consultados em 27/10/2026 cobrem serviços específicos, sem BigQuery na lista; a presença de “Budgets & caps” na tela não comprova proteção para consultas. Mesmo em serviços elegíveis, a aplicação do cap não é instantânea. Confirmar o produto e o escopo, sem prometer teto financeiro absoluto.
- **Resultado da tentativa anônima (histórico):** naquele momento, existência/permissão efetiva, esquema vivo, edição, volume processado, quota disponível, custo e exportação permaneciam a confirmar. Nenhuma consulta SQL, criação de recurso, extração ou transferência de registros foi realizada. O esquema JSON da API não deve ser presumido equivalente ao esquema da tabela BigQuery.

#### Acesso autenticado e esquema vistos em capturas — 27/10/2026

O usuário abriu a tabela externa `opencnpj-bigquery.public.receita` e enviou capturas de **Esquema** e **Detalhes**. Isso confirma existência e acesso aos metadados nessa sessão, não execução autorizada de consultas ou cobertura municipal. As capturas mostram:

- 73.797.761 linhas; 44,82 GB lógicos e 5,09 GB físicos, conforme unidades exibidas pelo console. Não confundir esses valores com bytes faturáveis de uma consulta ou tamanho do ZIP.
- Localização `us-east1`; última modificação em **14/10/2026, 06:44:25 UTC−3**. Essa data da tabela não comprova a competência da Receita ou atualização individual.
- `cnpj`, `cnae_principal`, `situacao_cadastral`, `uf`, `municipio` e **`codigo_municipio`** como STRING/NULLABLE. Portanto, diferente do JSON Schema público antes lido, a tabela BigQuery contém um código municipal. Ainda não está confirmado se é código Receita, IBGE ou outra referência; não comparar diretamente com `3302700`.
- Datas como DATE, `capital_social` NUMERIC, opções Simples/MEI BOOLEAN e campos aninhados RECORD. Não reutilizar o leitor do JSON sem adaptar o contrato; não selecionar QSA/contatos para o exercício municipal.

O usuário foi orientado a configurar `1073741824` bytes (1 GiB) em **Máximo de bytes cobrados** no editor original. A captura enviada anteriormente mostrou o campo ainda vazio; não há evidência visual do valor salvo. Conferir a configuração no editor utilizado antes de qualquer execução. As telas de orçamento mostraram alertas de 50%, 90% e 100% para R$ 15, mas não houve captura de confirmação de salvamento; não declarar orçamento ativo apenas pelo formulário.

**Próximo exercício, somente proposta para validação/estimativa no editor:**

```sql
SELECT DISTINCT
  municipio,
  codigo_municipio,
  uf
FROM `opencnpj-bigquery.public.receita`
WHERE uf = 'RJ'
  AND UPPER(TRIM(municipio)) IN ('MARICA', 'MARICÁ')
LIMIT 10;
```

Esse SQL usa somente campos observados e as duas grafias de Maricá para investigar a chave municipal; não conta concorrentes, não filtra CNAE/situação e não valida cobertura. Colar no editor para observar a mensagem de validação e os bytes estimados, **sem clicar em Executar**. Manter o teto e, se a estimativa ultrapassá-lo, revisar antes de propor qualquer aumento. `LIMIT` limita saída, não garante leitura pequena. Se o console exigir localização, usar `us-east1`, correspondente à tabela ([regra de localização](https://docs.cloud.google.com/bigquery/docs/locations)). Ainda não há validação do SQL, estimativa recebida ou resultado de consulta; depois revisar a possibilidade de uma execução pequena e autorizada para conferir valores e mapeamento.

**Evidência posterior do validador (27/10/2026):** captura do usuário mostrou SQL válido, estimativa **1,53 GB** e teto salvo no editor de **1.073.741.824 bytes (1 GiB)**. Não apareceu resultado de execução. A proposta acima está validada pelo console, mas excede o teto; não executar nem aumentá-lo sem decisão do usuário. A [tabela de preços oficial](https://cloud.google.com/bigquery/pricing?hl=es-419) consultada publica referência de **US$ 6,25/TiB** para consultas sob demanda e primeiro 1 TiB mensal gratuito, sujeito às condições. Como ordem de grandeza, 1,53 GB nessa referência representa aproximadamente **US$ 0,01 somente de processamento**, antes de franquias e tributos; isso não é orçamento em reais nem confirmação da cobrança efetiva. A própria página informa que contas em outra moeda seguem os SKUs dessa moeda. Consumo prévio, modelo efetivo, preço local e benefícios da conta não foram verificados. Um teto de 2 GiB (2.147.483.648 bytes) é alternativa a submeter ao usuário para este teste, não autorização de consulta nem orçamento mensal. Enquanto não houver decisão, manter o teto atual e não executar. Não foi realizado dry run separado pela API: a evidência é a validação automática do editor.

**Autorização posterior (27/10/2026):** o usuário aceitou expressamente o custo estimado e o teto de **2 GiB (2.147.483.648 bytes)** para **uma execução do SQL municipal acima**, sem mudar os filtros. O ajuste e a execução serão feitos pelo usuário; resultados ainda não recebidos. Isso não autoriza consultas adicionais, teto superior, extração ou importação. Se SQL/estimativa mudarem, reavaliar antes de executar. Manter o histórico anterior de teto de 1 GiB como evidência da etapa de validação, não como estado final já substituído.

#### Resultado municipal recebido e próximo teste — 27/10/2026

A captura de resultados do usuário mostrou sucesso do SQL autorizado e **`municipio=MARICA`, `codigo_municipio=5853`, `uf=RJ`**, com teto de 2 GiB visível. É uma combinação distinta, não o total de estabelecimentos. O [construtor de projeção do OpenCNPJ](https://github.com/Hitmasu/OpenCNPJ/blob/main/src/ETL/Modules/Receita/Processors/ShardQueryBuilder.cs) usa `e.codigo_municipio` dos estabelecimentos e faz o vínculo com a tabela auxiliar da Receita; o [leitor do leiaute](https://github.com/Hitmasu/OpenCNPJ/blob/main/src/ETL/Modules/Receita/Processors/TableSchemas.cs) confirma essa origem. O [IBGE](https://www.ibge.gov.br/cidades-e-estados/rj/marica.html) identifica Maricá por `3302700`. Portanto, para esse município/RJ, o vínculo observado é **Receita `5853` ↔ IBGE `3302700`**; não inserir o código de quatro dígitos na coluna local `codigo_ibge`.

Após o usuário iniciar o MySQL, consulta somente de leitura confirmou Maricá/RJ (`codigo_ibge=3302700`, id interno 41 nesta máquina) e atividade `5611201`/RESTAURANTES E SIMILARES. A tentativa anterior fora recusada pela conexão; o acesso foi restabelecido sem modificar banco/configuração pelo agente. O id 41 não é referência entre fontes e não deve ser fixado entre máquinas.

Próxima proposta, **somente para validar e observar bytes estimados**, sem execução autorizada ainda:

```sql
SELECT
  cnpj,
  cnae_principal,
  situacao_cadastral,
  codigo_municipio,
  uf
FROM `opencnpj-bigquery.public.receita`
WHERE uf = 'RJ'
  AND codigo_municipio = '5853'
  AND cnae_principal = '5611201'
  AND situacao_cadastral = 'Ativa'
LIMIT 10;
```

O gerador público mapeia situação `02` para `Ativa`; o contrato dessa amostra não depende de CNAEs secundários nem coleta QSA/contatos. A consulta pretende conferir identificadores/filtros de até 10 estabelecimentos, não um total confiável. Sua estimativa ainda não foi recebida; manter o teto de 2 GiB, não elevar ou executar automaticamente. A autorização anterior era de uma execução do SQL municipal e já foi utilizada. Nenhum leitor/importador, tabela ou alteração MySQL foi implementado. Próximas pendências: revisar a estimativa, obter autorização de nova execução se viável, conferir CNPJs/filtros e definir um mapeamento reproduzível dos 92 municípios antes da carga completa. Detalhes do job anterior e custo efetivo ainda não foram observados.

**Estimativa da amostra recebida (27/10/2026):** o editor validou o SQL acima e estimou **2,96 GB**, mantendo teto de **2 GiB**. Não houve resultado de execução. O valor é superior ao teto; não usar `LIMIT 10` como proteção de custo. Um teto de **3 GiB (3.221.225.472 bytes)** é proposta para uma execução dessa amostra, ainda dependente de autorização do usuário. Pela referência oficial US$ 6,25/TiB, a ordem de grandeza é US$ 0,02 de processamento, não uma confirmação da fatura, da franquia restante ou do preço em reais. Não remover o teto nem autorizar outras consultas por consequência deste teste.

**Autorização da amostra (27/10/2026):** o usuário autorizou expressamente teto de **3 GiB (3.221.225.472 bytes)** e uma execução do SQL de amostra já validado com estimativa de 2,96 GB. O usuário fará o ajuste e a execução na mesma aba; ainda não há evidência de salvamento do novo teto ou resultados. Se SQL/estimativa mudarem, reavaliar antes de executar. Essa autorização não inclui consultas adicionais, aumento posterior ou carga no MySQL.

#### Resultado da amostra e próxima validação local — 27/10/2026

- O usuário executou o SQL autorizado e enviou captura com sucesso e **10 linhas**. Os campos visíveis correspondem a CNAE principal `5611201`, situação `Ativa` e código Receita `5853`. UF não aparece no recorte da captura; o SQL anterior inclui `uf = 'RJ'`.
- Pelo menos um CNPJ começa com zero. Identificadores devem permanecer texto; a imagem não substitui validação de formato, duplicatas e filtros no arquivo. O `LIMIT 10` não comprova total ou cobertura municipal.
- Próximo passo proposto: **Salvar resultados → JSON → arquivo local**, usando o resultado já executado. A [documentação do BigQuery](https://docs.cloud.google.com/bigquery/docs/export-file) identifica esse formato como JSON delimitado por novas linhas (NDJSON), não necessariamente um array JSON. Não executar outra consulta para baixá-lo.
- Destino local sugerido: `Right_Point/storage/app/amostras/cnpj-marica-5611201.json`; criar a pasta se necessário. Caminho confirmado como ignorado pelo Git. O arquivo não foi recebido/validado e não acompanha a troca de computador pelo pull; não versionar a amostra real automaticamente. Testes reproduzíveis podem usar dados sintéticos posteriormente.
- Após receber o arquivo, orientar o usuário na leitura/validação PHP, sem gravar no MySQL. Aquisição completa, vínculo dos 92 municípios, termos dos dados e regra de concorrência continuam pendentes. Consultar informações do job para bytes processados/faturados e cache; estimativa anterior não comprova custo efetivo.

#### Conferência do arquivo salvo — 27/10/2026

- O usuário salvou um arquivo de 1.400 bytes em `Right_Point/storage/app/amostras/`, com nome automático do download. O agente fez leitura local e conferência avulsa em PHP, sem alterar o arquivo: **array JSON completo**, e não NDJSON. A orientação anterior sobre leitura por linha não se aplica a esta exportação; usar `json_decode` sobre o conteúdo completo.
- Resultado: 10 objetos, 10 CNPJs distintos, campos `cnpj`, `cnae_principal`, `situacao_cadastral`, `codigo_municipio` e `uf` presentes como texto; todos correspondem aos filtros `5611201`/`Ativa`/`5853`/`RJ`. CNPJs têm 14 dígitos e um preserva zero inicial. Nenhuma divergência nessa conferência; dígitos verificadores, atualidade e cobertura não foram verificados.
- SHA-256 local para identificar a cópia conferida: `92f484634c3eeda2d503c2655750f997591e8d3ada04d646a710714af00d1ba9`. O hash identifica o arquivo, não certifica procedência oficial ou autenticidade da fonte.
- Evolução posterior: usuário implementou `VerificarAmostraCnpj`, assinatura `cnpj:verificar-amostra {arquivo?}`. Sintaxe passou; amostra padrão retornou 10 registros aprovados/saída 0 e arquivo fictício repetido retornou falha/saída 1. Sem consultas externas ou importação. O teste de filtro divergente e a automatização dos demais casos foram adiados pelo usuário. A amostra permanece ignorada pelo Git.

### Arquivos da base completa como alternativa




Em 20/10/2026, o agente consultou o [leiaute oficial de dados abertos de CNPJ](https://www.gov.br/receitafederal/dados/cnpj-metadados.pdf) e confirmou a pasta `2026-10` no repositório da Receita/SERPRO. O ponto de entrada público é o [catálogo oficial do CNPJ](https://www.gov.br/receitafederal/pt-br/acesso-a-informacao/dados-abertos/cadastros/cnpj), que direciona ao Portal Brasileiro de Dados Abertos. Foram consultados somente nomes e tamanhos; nenhum ZIP de dados foi baixado ou extraído. O endereço antigo `/dados/cnpj/dados_abertos_cnpj/` retornou 404 nesta conferência.

| Arquivos confirmados na pasta `2026-10` | Uso proposto no RightPoint |
| --- | --- |
| `Municipios.zip` (43.443 bytes) | Conferir códigos e nomes usados pela Receita e estudar a correspondência com o catálogo IBGE. |
| `Cnaes.zip` (22.078 bytes) | Conferir compatibilidade dos códigos e descrições com o catálogo já carregado. |
| `Estabelecimentos0.zip` a `Estabelecimentos9.zip` | Fonte principal para identificação de estabelecimentos, situação cadastral, CNAE principal, UF e município. |
| `Empresas0.zip` a `Empresas9.zip` | Possível complemento posterior para razão social e porte; não é necessário para o primeiro leitor de estabelecimentos. |

As dez partes de estabelecimentos listadas somam 5.416.929.969 bytes compactados (cerca de 5,42 GB); `Estabelecimentos1.zip` tem 341.948.627 bytes e pode servir como candidato para um exercício posterior de amostra. Não foi validado o conteúdo de nenhuma parte nem a cobertura de um município. Uma amostra isolada não sustenta contagem completa ou resultado zero de concorrentes. Para uma carga completa futura, planejar o processamento das partes da mesma edição e o filtro de UF/município, antes de anunciar cobertura.

Próxima ação sugerida ao usuário: começar pelos pacotes pequenos `Municipios.zip` e `Cnaes.zip` da mesma pasta, extrair e examinar seus arquivos. Armazenamento local proposto: `Right_Point/storage/app/receita/2026-10/`, confirmado como ignorado pelo Git. O leitor deve receber o arquivo extraído, não o ZIP. O formato descrito no leiaute usa ponto e vírgula; o exemplo de dois campos em `storage/teste-leitura.csv` é somente um teste de leitura, sem representar um estabelecimento real.

Licença, codificação e leiaute efetivo da edição, cobertura, atualização, compatibilidade dos CNAEs e correspondência municipal continuam a conferir nos dados e metadados antes de importar. Não afirmar cálculo de sobrevivência/mortalidade somente a partir de situação cadastral e data de abertura.

## Experimento de leitura no Laravel

Em 05/10/2026, o comando `php artisan ibge:consultar-rio` consultou as duas APIs, associou as respostas pelo código `3304557` e exibiu Rio de Janeiro, população `6211223` pessoas e ano `2022`. O comando verifica falha de conexão, resposta HTTP sem sucesso, formato inesperado e valor não numérico. A execução com acesso à rede foi bem-sucedida; a execução em ambiente de ferramentas com rede restrita não conseguiu conectar, sem indicar defeito no código.

O experimento `ibge:consultar-rio` continua sem gravação ou score; os comandos separados `ibge:importar-municipios-rj` e `ibge:importar-populacao-rj` persistem, respectivamente, o catálogo municipal e a população de 2022 dos 92 municípios. A URL do experimento `ibge:consultar-rio` cobre somente **um** município; o importador de população usa a consulta agregada para todo o RJ. A carga atual é manual e registra procedência e data. A direção aprovada para o futuro é atualização **agendada, de uma fonte por vez**, com frequência e retentativas ainda `A CONFIRMAR`; consultas dos usuários usam dados locais já validados, não chamam todas as fontes externas a cada análise.
