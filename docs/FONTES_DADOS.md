# Fontes de dados candidatas — RightPoint

Registro inicial do grupo, atualizado em 05/10/2026. Uma fonte listada aqui não está automaticamente integrada nem aprovada para o cálculo do score. Antes de usá-la, conferir acesso, licença, cobertura geográfica, período, unidade, atualização e qualidade dos dados.

| Fonte candidata | Dados pretendidos | Possível uso | Estado |
| --- | --- | --- | --- |
| CNPJ Aberto / Receita Federal | CNPJ, CNAE, situação, endereço e abertura | Identificação de estabelecimentos e concorrência; sobrevivência/mortalidade exige critério e dados históricos apropriados | Endpoint e viabilidade A CONFIRMAR |
| IBGE — Localidades e Censo/SIDRA | Municípios, população, renda, densidade e perfil etário, conforme tabela disponível | Delimitação municipal e indicadores socioeconômicos | Dois endpoints testados abaixo; os demais indicadores não foram testados |
| ANP | Preços de combustíveis por região | Possível indicador indireto, cuja relação com fluxo/movimento ainda precisa ser justificada | A CONFIRMAR |
| INMET | Temperatura, chuva e histórico climático | Avaliar influência climática conforme a atividade | A CONFIRMAR |
| Portal da Transparência / SICONFI | Obras e investimentos públicos | Investigar investimento regional; não presumir valorização como consequência | A CONFIRMAR |
| OpenStreetMap / Mapbox | Coordenadas, ruas, bairros, pontos de interesse e geocodificação | Consulta espacial e mapa | A CONFIRMAR |

## Referências oficiais identificadas em 05/10/2026

Estas referências mostram caminhos possíveis de obtenção de dados. Seus arquivos, campos, licença de reutilização, cobertura e adequação ao RightPoint ainda precisam ser avaliados antes de qualquer importação.

- **CNAE:** [busca oficial da CONCLA/IBGE](https://concla.ibge.gov.br/busca-online-cnae.html) e [leiaute dos dados abertos de CNPJ da Receita Federal](https://www.gov.br/receitafederal/dados/cnpj-metadados.pdf), que descreve arquivo de códigos e descrições de CNAE. Definir qual arquivo e versão alimentarão `atividades_economicas`.
- **Estabelecimentos/CNPJ:** [catálogo de dados abertos da Receita Federal](https://www.gov.br/receitafederal/pt-br/acesso-a-informacao/dados-abertos/cadastros) e [leiaute de CNPJ](https://www.gov.br/receitafederal/dados/cnpj-metadados.pdf). O arquivo efetivo, o método de obtenção e o mapeamento do município da Receita para o código IBGE seguem A CONFIRMAR. A Receita [iniciou a emissão de CNPJs alfanuméricos em 2026](https://www.gov.br/receitafederal/pt-br/assuntos/noticias/2026/julho/receita-federal-gera-o-primeiro-cnpj-em-formato-alfanumerico): armazenar o identificador como texto de 14 posições, quando essa tabela for criada.
- **ANP:** [série histórica de preços de combustíveis em CSV](https://www.gov.br/anp/pt-br/centrais-de-conteudo/dados-abertos/serie-historica-de-precos-de-combustiveis). Preço pesquisado não equivale diretamente a fluxo de pessoas nem a potencial comercial.
- **INMET:** [BDMEP e dados históricos](https://portal.inmet.gov.br/servicos/bdmep-dados-historicos). As medições são de estações; decidir como relacioná-las a municípios antes de usar um indicador municipal.
- **Investimento público:** [API de dados contábeis do SICONFI](https://www.tesourotransparente.gov.br/consultas/consultas-siconfi/siconfi-api-de-dados-abertos) e [API de obras do Obrasgov](https://www.gov.br/obrasgov/pt-br/ferramentas-de-gestao-e-transparencia/api-de-dados-abertos-obrasgov.br). São conjuntos distintos; selecionar uma medida concreta antes de criar indicador.
- **Geocodificação:** [política de uso do Nominatim público, ligado ao OpenStreetMap](https://operations.osmfoundation.org/policies/nominatim/). O recorte inicial por município não exige geocodificação nem Mapbox.

A migration `create_fonte_dados_table` cria `fontes_dados` para registrar a origem dos dados efetivamente usados. Ela não importa as fontes candidatas nem substitui este documento de pesquisa.

## 1. IBGE — municípios do estado do Rio de Janeiro

Endpoint fornecido pelo usuário:

https://servicodados.ibge.gov.br/api/v1/localidades/estados/33/municipios?

Consulta de leitura realizada em 05/10/2026: retornou 92 municípios. Cada item inclui `id` (código IBGE), `nome` e dados da divisão territorial; a sigla `RJ` aparece na estrutura aninhada da UF. O município do Rio de Janeiro tem código `3304557`. Esta resposta **não** traz população nem coordenadas. O `?` final da URL não adiciona nenhum parâmetro.

Mapeamento possível para a tabela `municipios` já criada: `id` da API → `codigo_ibge` (guardar como texto de sete caracteres); `nome` → `nome`; sigla da UF → `uf`. O `id` interno da tabela Laravel é diferente do código IBGE.

## 2. IBGE SIDRA — população residente do Rio de Janeiro no Censo 2022

Endpoint fornecido pelo usuário:

https://apisidra.ibge.gov.br/values/t/4714/n6/3304557/v/93/p/2022

Consulta de leitura realizada em 05/10/2026: a primeira posição do array é um cabeçalho que explica os campos. O registro seguinte contém `D1C=3304557` (código do município), `D2N=População residente`, `D3C=2022`, `MN=Pessoas` e `V=6211223`. O valor `V` vem como texto; validar antes de convertê-lo em número.

Esta URL pede **apenas população residente de 2022 para um município**. Ela não entrega renda, perfil etário, dados por bairro ou concorrentes. Como o recorte é municipal, não se deve atribuir esse total a um bairro ou a um raio específico.

O código IBGE `3304557` permite associar a resposta do SIDRA ao município da API de Localidades, sem depender do nome escrito da cidade.

## 3. Receita Federal — CNPJ

O usuário reservou esta seção, mas ainda não forneceu endpoint ou arquivo para teste. Acesso, licença, campos, cobertura e atualização permanecem A CONFIRMAR. Não afirmar cálculo de sobrevivência/mortalidade somente a partir de situação cadastral e data de abertura.

## Experimento de leitura no Laravel

Em 05/10/2026, o comando `php artisan ibge:consultar-rio` consultou as duas APIs, associou as respostas pelo código `3304557` e exibiu Rio de Janeiro, população `6211223` pessoas e ano `2022`. O comando verifica falha de conexão, resposta HTTP sem sucesso, formato inesperado e valor não numérico. A execução com acesso à rede foi bem-sucedida; a execução em ambiente de ferramentas com rede restrita não conseguiu conectar, sem indicar defeito no código.

Este experimento não grava dados no banco nem produz score. Antes de importar municípios ou armazenar indicadores, definir a fonte aprovada, o recorte, a periodicidade de atualização e como registrar a procedência dos dados.
