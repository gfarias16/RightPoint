# Fontes de dados candidatas — RightPoint

Registro inicial do grupo, atualizado em 14/10/2026. Uma fonte listada aqui não está automaticamente integrada nem aprovada para o cálculo do score. Antes de usá-la, conferir acesso, licença, cobertura geográfica, período, unidade, atualização e qualidade dos dados. A [matriz de dados e o fluxograma](./arquitetura/matriz-dados-diagnostico.md) organizam os critérios **propostos** para a primeira versão, limitada aos 92 municípios do RJ.

| Fonte candidata | Dados pretendidos | Possível uso | Estado |
| --- | --- | --- | --- |
| CNPJ / Receita Federal e APIs derivadas | CNPJ, CNAE, situação, endereço e abertura | Identificação de estabelecimentos e concorrência; sobrevivência/mortalidade exige critério e dados históricos apropriados | Minha Receita e Casa dos Dados em avaliação; teste gratuito retornou HTTP 429; nenhuma integração de concorrência implementada |
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

Para o próximo exercício, propor um comando de consulta somente de leitura, com timeout e tratamento de 429, antes de gravar no MySQL. Uma única página ou falha de requisição não representa o total de concorrentes. Confirmar o retorno real e a cobertura antes de exibir contagens. A consulta a serviços derivados não implica dados em tempo real da Receita nem dispensa o registro de procedência.

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
