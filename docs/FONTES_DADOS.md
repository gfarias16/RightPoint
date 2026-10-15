# Fontes de dados candidatas — RightPoint

Registro inicial do grupo, atualizado em 14/10/2026. Uma fonte listada aqui não está automaticamente integrada nem aprovada para o cálculo do score. Antes de usá-la, conferir acesso, licença, cobertura geográfica, período, unidade, atualização e qualidade dos dados. A [matriz de dados e o fluxograma](./arquitetura/matriz-dados-diagnostico.md) organizam os critérios **propostos** para a primeira versão, limitada aos 92 municípios do RJ.

| Fonte candidata | Dados pretendidos | Possível uso | Estado |
| --- | --- | --- | --- |
| CNPJ Aberto / Receita Federal | CNPJ, CNAE, situação, endereço e abertura | Identificação de estabelecimentos e concorrência; sobrevivência/mortalidade exige critério e dados históricos apropriados | Endpoint e viabilidade A CONFIRMAR |
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

Uma captura enviada pelo usuário do [endpoint oficial de metadados do agregado 4714](https://servicodados.ibge.gov.br/api/v3/agregados/4714/metadados) confirmou a pesquisa `Censo Demográfico`, nível municipal `N6` e a variável 93 com nome **População residente** e unidade **Pessoas**. A periodicidade mostrada é anual, com início e fim em 2022; este indicador deve ser rotulado como população do **Censo 2022**, não como população atual. Ainda não há gravação local da população nem registro de versão/data da fonte na tabela `fontes_dados`.

## 3. Receita Federal — CNPJ

O usuário reservou esta seção, mas ainda não forneceu endpoint ou arquivo para teste. Acesso, licença, campos, cobertura e atualização permanecem A CONFIRMAR. Não afirmar cálculo de sobrevivência/mortalidade somente a partir de situação cadastral e data de abertura.

## Experimento de leitura no Laravel

Em 05/10/2026, o comando `php artisan ibge:consultar-rio` consultou as duas APIs, associou as respostas pelo código `3304557` e exibiu Rio de Janeiro, população `6211223` pessoas e ano `2022`. O comando verifica falha de conexão, resposta HTTP sem sucesso, formato inesperado e valor não numérico. A execução com acesso à rede foi bem-sucedida; a execução em ambiente de ferramentas com rede restrita não conseguiu conectar, sem indicar defeito no código.

O experimento `ibge:consultar-rio` continua sem gravação ou score; o comando separado `ibge:importar-municipios-rj` já persiste o catálogo municipal. A consulta de população acima cobre somente **um** dos 92 municípios. Antes de armazenar indicadores, confirmar cobertura, método de carga, periodicidade e procedência. A direção aprovada para o futuro é atualização **agendada, de uma fonte por vez**, com frequência e retentativas ainda `A CONFIRMAR`; consultas dos usuários deverão usar dados locais já validados, não chamar todas as fontes externas a cada análise.
