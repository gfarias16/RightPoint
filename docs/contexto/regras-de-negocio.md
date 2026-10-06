# Regras de Negocio - RightPoint

## Objetivo

Este documento registra a primeira versao das regras de negocio do sistema RightPoint, com base no minimundo elaborado pelo grupo e nas orientacoes do trabalho.

O objetivo do RightPoint e auxiliar um empreendedor a avaliar o potencial comercial de uma atividade economica em uma determinada regiao ou localizacao, considerando dados publicos, concorrencia existente e indicadores relevantes para o tipo de negocio analisado.

Documentos relacionados: [minimundo estruturado](./minimundo.md), [restricoes de integridade](./restricoes-de-integridade.md), [glossario](./glossario.md), [limitacoes conhecidas](./limitacoes-conhecidas.md) e [matriz de dados e fluxograma](../arquitetura/matriz-dados-diagnostico.md).

## Escopo desta versao

Esta versao descreve somente regras de negocio, ou seja, condicoes que orientam como a realidade representada pelo sistema deve funcionar.

Restricoes de integridade, classes, atributos e relacionamentos estao detalhados em documentos proprios e permanecem sujeitos a validacao do grupo.

O **recorte inicial escolhido** e o conjunto dos 92 municipios do estado do Rio de Janeiro, selecionados por nome ou pelo mapa e identificados pelo codigo IBGE. A atividade sera identificada por CNAE. As regras gerais sobre bairro, ponto e raio permanecem como possibilidades futuras, nao como requisitos da primeira entrega. Os criterios de fontes e concorrencia abaixo sao **propostas para validacao academica**, nao uma formula de score aprovada.

## Conceitos principais

- Empreendedor: usuario interessado em avaliar a abertura de um negocio.
- Regiao geografica: bairro, municipio ou outro recorte territorial analisavel pelo sistema.
- Localizacao: ponto geografico informado ou selecionado pelo usuario para uma analise.
- Atividade economica: tipo de negocio que pode ser avaliado, preferencialmente associado a um CNAE ou fonte comercial equivalente.
- Estabelecimento existente: empresa identificada em base publica de CNPJ ou fonte equivalente.
- Concorrente: estabelecimento existente cuja atividade economica e considerada equivalente ou relacionada a atividade analisada.
- Analise de potencial: avaliacao feita pelo sistema para uma atividade economica em uma regiao ou localizacao.
- Score: nota entre 0 e 100 que representa o potencial comercial calculado para a analise.
- Justificativa: explicacao textual dos principais fatores que influenciaram o score.

## Regras de negocio

### RN-001 - Analise vinculada a atividade economica

Toda analise de potencial deve estar associada a uma atividade economica informada pelo usuario.

A atividade pode representar negocios como padaria, barbearia, restaurante, loja ou outro tipo de empreendimento previsto pelo sistema.

### RN-002 - Analise vinculada a regiao ou localizacao

Toda analise de potencial deve considerar uma regiao geografica ou uma localizacao informada pelo usuario.

Quando a analise for feita por localizacao pontual, o sistema deve usar essa localizacao como referencia para identificar indicadores e concorrentes.

### RN-003 - Atividade economica relacionada a CNAE ou fonte equivalente

As atividades economicas analisadas pelo sistema devem estar relacionadas a CNAEs ou a fontes comerciais equivalentes que permitam identificar estabelecimentos comparaveis.

Na primeira versao, a atividade escolhida sera identificada por CNAE. A relacao com categorias internas ou CNAEs equivalentes continua `A CONFIRMAR`.

### RN-004 - Uso de dados publicos da regiao

O sistema deve considerar informacoes socioeconomicas da regiao analisada, obtidas de fontes publicas ou fontes equivalentes.

Indicadores iniciais previstos no minimundo incluem populacao, densidade populacional, renda media, faixa etaria e clima.

Proposta para a primeira analise: populacao municipal do IBGE como dado regional minimo. Renda, densidade e faixa etaria dependem da validacao das tabelas e da pertinencia para a atividade. Ver [matriz de dados](../arquitetura/matriz-dados-diagnostico.md).

### RN-005 - Uso de dados publicos de empresas existentes

Os estabelecimentos existentes devem ser obtidos a partir de dados publicos de CNPJ ou fonte equivalente.

Esses dados devem permitir, quando disponivel, identificar CNPJ, atividade economica, situacao cadastral, localizacao e tempo de existencia do estabelecimento.

### RN-006 - Identificacao de concorrentes por atividade

Para calcular a concorrencia, o sistema deve considerar como concorrentes os estabelecimentos cuja atividade economica seja igual, equivalente ou relacionada a atividade analisada.

A CONFIRMAR: quais atividades serao consideradas equivalentes entre si quando nao tiverem exatamente o mesmo CNAE.

Proposta inicial: contar somente estabelecimentos com CNAE **principal exatamente igual** ao consultado. CNAEs secundarios ou equivalentes nao entram nessa primeira contagem.

### RN-007 - Identificacao de concorrentes por area analisada

O sistema deve identificar concorrentes existentes dentro da regiao ou do raio geografico definido para a analise.

Quando o usuario informar um raio, a concorrencia deve ser calculada a partir da distancia entre a localizacao analisada e os estabelecimentos encontrados.

Na primeira versao, a area de concorrencia e o municipio selecionado pelo codigo IBGE; raio e ponto ficam para etapa posterior. Uma contagem zero so significa ausencia de concorrentes apos verificar a cobertura da base para o municipio e a atividade.

### RN-008 - Raio geografico definido pelo usuario

Quando a analise for baseada em um ponto geografico, o usuario podera escolher um raio em metros ou quilometros para limitar a busca por concorrentes.

A CONFIRMAR: valores minimo e maximo permitidos para o raio.

### RN-009 - Calculo de score entre 0 e 100

Cada analise **com dados minimos suficientes e formula aprovada** deve gerar um score numerico entre 0 e 100. Uma consulta pode retornar indicadores e avisos sem score quando essas condicoes nao forem atendidas.

Quanto maior o score, maior deve ser o potencial comercial estimado para a atividade naquela localizacao ou regiao.

### RN-010 - Score baseado em multiplos fatores

O score deve ser calculado a partir do cruzamento de indicadores da regiao, da atividade economica e da concorrencia existente.

Exemplos de fatores previstos no minimundo:

- quantidade de concorrentes;
- densidade populacional;
- renda media;
- compatibilidade entre renda da regiao e tipo de negocio;
- faixa etaria predominante;
- tempo de existencia de estabelecimentos semelhantes, quando disponivel;
- clima, quando relevante para a atividade analisada.

A CONFIRMAR: peso de cada fator no calculo do score.

### RN-011 - Justificativa obrigatoria para o resultado

Toda analise que apresentar score deve trazer uma justificativa compreensivel. Quando nao houver score, deve explicar a limitacao.

A justificativa deve indicar os principais fatores que influenciaram o resultado, como baixa concorrencia, renda compativel, alta densidade populacional ou outros fatores relevantes para a atividade consultada.

### RN-012 - Justificativa coerente com os indicadores usados

A justificativa apresentada ao usuario deve ser coerente com os indicadores efetivamente utilizados no calculo do score.

O sistema nao deve justificar o resultado com fatores que nao participaram da analise.

### RN-013 - Historico da analise

O sistema podera armazenar o historico das analises realizadas.

Quando armazenado, o historico deve guardar o score calculado e os indicadores utilizados, permitindo entender quais dados produziram determinado resultado.

### RN-014 - Preservacao do resultado historico

Uma analise historica deve continuar representando os dados usados no momento em que foi calculada, mesmo que bases publicas sejam atualizadas posteriormente.

Atualizacoes futuras nas bases publicas nao devem alterar retroativamente o resultado historico ja registrado.

### RN-015 - Nova analise apos atualizacao de dados

Quando dados publicos forem atualizados, uma nova avaliacao da mesma atividade e localizacao podera gerar resultado diferente da analise anterior.

Nesse caso, o novo resultado deve ser tratado como uma nova analise, preservando a analise historica anterior.

### RN-016 - Situacao cadastral do estabelecimento

A situacao cadastral do estabelecimento deve ser considerada na interpretacao dos dados de concorrencia.

Proposta inicial: somente estabelecimentos com situacao cadastral **ativa** participam da contagem de concorrentes. Situacoes desconhecidas nao devem ser convertidas em ativas. Exibicao separada de inativos, baixados ou suspensos continua `A CONFIRMAR`.

### RN-017 - Atividades sem dados suficientes

Quando nao houver dados suficientes para calcular uma analise confiavel, o sistema deve indicar essa limitacao ao usuario.

Para a primeira versao, sem populacao municipal, sem cobertura validada de CNPJ ou sem formula/pesos aprovados, a consulta **nao apresenta score**; mostra os dados disponiveis e o motivo da insuficiencia. Quando um dado opcional faltar, indicar a ausencia e somente calcular com fatores cuja regra de uso tenha sido aprovada. A validade temporal de cada fonte continua `A CONFIRMAR`.

### RN-018 - Resultado orientativo

O score do RightPoint deve ser interpretado como apoio a decisao do empreendedor, nao como garantia de sucesso comercial nem como probabilidade estatistica de sucesso.

O sistema deve orientar a analise, mas a decisao final de abrir ou nao o negocio pertence ao usuario.

## Candidatas a restricoes de integridade

Os itens abaixo parecem importantes, mas pertencem melhor ao documento de restricoes de integridade:

- o score calculado nao pode ser menor que 0 nem maior que 100;
- toda analise deve possuir atividade economica;
- toda analise deve possuir regiao ou localizacao;
- CNPJ de estabelecimento deve ser unico quando armazenado;
- um estabelecimento deve estar associado a uma atividade economica;
- o raio informado para analise nao pode ser negativo;
- indicadores historicos usados em uma analise registrada nao devem ser apagados sem preservar a rastreabilidade.

## Pontos para validar com o grupo

- Quais tipos de negocio serao exemplos oficiais do trabalho?
- A professora/grupo validam o recorte dos 92 municipios do RJ, a populacao como dado regional minimo e a proposta de concorrencia por CNAE principal exato e situacao ativa?
- Quais indicadores adicionais tem cobertura e pertinencia comprovadas para cada atividade?
- Como o score sera calculado em alto nivel?
- Como identificar e apresentar estabelecimentos inativos fora da contagem inicial?
- O historico de analise sera obrigatorio ou opcional na modelagem final?
- O usuario precisa estar cadastrado para realizar analises ou isso ficara fora do escopo?
