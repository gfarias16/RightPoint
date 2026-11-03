# Critérios propostos para o diagnóstico piloto

Registrado em 03/11/2026. **Proposta técnica para discussão; não implementada nem aprovada como fórmula definitiva.** O usuário escolheu restaurantes, padarias e barbearias como os três perfis iniciais. A composição dos CNAEs, a interpretação dos fatores e os pesos abaixo são recomendações do agente, sujeitas à revisão do grupo. Podemos elaborar e testar a proposta antes da validação acadêmica, distinguindo sempre proposta, experimento e resultado validado.

Referências do projeto: [regras de negócio](../contexto/regras-de-negocio.md), [integridade](../contexto/restricoes-de-integridade.md), [matriz](../arquitetura/matriz-dados-diagnostico.md) e [estado local](../STATUS.md).

**Avanço implementado em 03/11:** validação/importação preparadas para os quatro CNAEs separados, com grupos ausentes indisponíveis e fotografia conferida exigida na gravação. Usuário já obteve a nova exportação; simulação real aprovou 368 pares, mantendo banco e arquivo preservados. Metadados do novo job ainda pendentes e gravação bloqueada; banco segue com restaurantes. Isso não implementa a união de padarias, classificação exclusiva de barbearias ou os cálculos/score propostos abaixo. Ver [procedimento](../procedimentos/contagens-perfis-piloto.md).

## 1. Perfis e alcance dos CNAEs

| Perfil escolhido | CNAE proposto | O que a seleção representa |
| --- | --- | --- |
| Restaurantes | `5611201` | Restaurantes e similares; manter o recorte já importado. |
| Padarias | `1091102` e `4721102` | Produção própria predominante e revenda predominante, respectivamente; registrar cada subclasse e propor sua união explícita no perfil. As classificações também abrangem confeitaria. |
| Barbearias | `9602501` | Aproximação pelo grupo de cabeleireiros, manicure e pedicure. Inclui a ocupação de barbeiro, mas não isola barbearias. |

Os quatro códigos e suas descrições foram encontrados no catálogo MySQL local em consulta somente de leitura. A versão da classificação recebida pela API continua a confirmar; não é determinada apenas pela quantidade de subclasses.

Proposta de apresentação: manter o perfil desejado pelo usuário separado do alcance observado da fonte. No caso de barbearias, mostrar **estabelecimentos do grupo CNAE de cabeleireiros, manicure e pedicure**, com aviso de que não são exclusivamente barbearias. Um total exclusivo de barbearias exigirá dados/classificação adicionais e validação própria; pesquisar palavras no nome comercial não é método suficiente por si só.

Agrupar padarias é uma ampliação proposta do critério atual de CNAE principal exato. O sistema atual consulta um CNAE por vez; escolher três perfis não implementa automaticamente uma categoria com vários CNAEs. Até revisar a regra, as duas subclasses podem ser apresentadas separadamente. Não incluir indústria de panificação, lanchonetes ou CNAEs secundários silenciosamente.

## 2. Regras iniciais sugeridas

1. Comparar municípios do RJ, usando o código IBGE como referência territorial.
2. Contar apenas estabelecimentos formalmente ativos com CNAE **principal** pertencente ao conjunto explícito do perfil. Situação desconhecida não vira ativa.
3. Cada CNPJ textual deve contribuir uma vez para a contagem de um perfil/município na mesma fotografia. Preservar códigos com zeros iniciais. Preferir contagem distinta da união na fonte para padarias; não somar amostras, datas diferentes ou histórico de coletas.
4. Comparar municípios dentro do mesmo perfil e com o mesmo recorte de fonte, referência, filtros e método. Não comparar diretamente uma nota de restaurante com uma de barbearia como se medissem o mesmo mercado.
5. Ausência de coleta, indicador inválido, população não positiva ou fator obrigatório ausente impede o cálculo correspondente. Não substituir por zero nem redistribuir pesos silenciosamente.
6. Mostrar fonte, data de consulta, período dos dados e limitações. A contagem da fonte não garante cobertura de informais, funcionamento real, capacidade de atendimento ou concorrência comercial completa.
7. Explicar somente os fatores calculados. Mudanças de regra devem ter versão; análises históricas, se implementadas, devem preservar fatores e dados usados.

## 3. Primeiro indicador calculável

Usar a razão descritiva:

```text
oferta formal registrada por 10 mil habitantes = contagem / população × 10.000
```

Exemplos aritméticos dos dados já conferidos para restaurantes:

| Município | População do Censo 2022 | Contagem na base | Razão por 10 mil habitantes |
| --- | ---: | ---: | ---: |
| Barra Mansa | 169.894 | 308 | 18,13 |
| Maricá | 197.277 | 620 | 31,43 |

Esses quocientes misturam população de 2022 com uma fotografia CNPJ de período desconhecido. São **exemplos exploratórios**, não taxas contemporâneas validadas nem medida de demanda/saturação. Eventual exibição deve declarar as duas referências e preservar a ausência de score. Não foram adicionados ao controller ou à interface.

Mais estabelecimentos por habitante pode indicar maior oferta registrada; também pode coexistir com turismo, circulação de trabalhadores, diferenças de formalização ou maior demanda. Menor razão não demonstra oportunidade automaticamente. População municipal não mede fluxo de pedestres ou consumidores efetivos do estabelecimento.

## 4. Segunda dimensão a investigar

Propor **rendimento domiciliar per capita municipal do Censo 2022** como aproximação inicial da capacidade de consumo, sem tratá-lo como gasto com a atividade. A divulgação de Trabalho e Rendimentos do Censo apresenta essa variável; a tabela/API, unidade exata, cobertura dos 92 municípios e qualidade dos resultados precisam ser verificadas antes de importar. Não substituir renda municipal por um valor estadual da PNAD ou por PIB per capita.

Renda mais alta não significa necessariamente melhor adequação a qualquer negócio. Um restaurante popular e outro de maior preço podem buscar públicos diferentes; perfis de preço/público são uma evolução proposta, não campos ou regras já implementados. Não atribuir público exclusivamente masculino a toda barbearia sem definição do perfil.

## 5. Hipótese de score para experimentar depois

Após validar os dados e aceitar a interpretação, experimentar um **índice relativo de contexto de mercado**, de 0 a 100:

```text
índice experimental = 0,50 × capacidade de consumo relativa
                   + 0,50 × menor oferta formal relativa
```

Ambos os componentes precisam estar na mesma escala de 0 a 100. Como primeiro método a avaliar, usar a posição percentual no conjunto de municípios do mesmo perfil, com empates pela posição média: `100 × (posição_média − 1) / (N − 1)`, para `N > 1`. Inverter a posição da razão de oferta: `100 − posição_percentual`. Isso representa a hipótese discutível de que menor oferta relativa pode favorecer entrada; não é uma relação demonstrada com sucesso comercial. Não escolher limiares de saturação ou promessa de potencial alto por inferência.

Os pesos 50/50 são uma hipótese simples por ausência de evidência para priorizar um componente, não pesos aprendidos ou cientificamente comprovados. Não adicionar a população absoluta como terceiro bônus automaticamente: ela já participa da razão de oferta. Qualidade/cobertura dos dados deve aparecer separadamente e condicionar a elegibilidade, sem elevar a nota comercial.

Antes de publicar, fixar e registrar o conjunto comparado, fontes, referências e versão do método; tratar dados faltantes de forma explícita. Se todos os valores de um componente forem iguais, a posição média resulta em 50 e o componente não diferencia municípios. Um conjunto com menos de dois municípios não gera esse índice. Nota relativa depende do conjunto de comparação e não é probabilidade de sucesso ou recomendação financeira.

Testar pesos 30/70, 50/50 e 70/30 e identificar municípios cuja posição muda muito. Essa é análise de sensibilidade: torna visível quanto a classificação depende das escolhas do modelo. O teste não comprova eficácia comercial. A fórmula acima não está autorizada como regra definitiva pelo simples fato de estar documentada; o aplicativo mantém score NULL.

## 6. Sequência prática

1. Revisar o mapeamento proposto, especialmente união de padarias e alcance amplo de barbearias.
2. Para o próximo exercício de programação, orientar uma função de cálculo da razão descritiva e dos estados inválidos/ausentes, fora da view, com testes úteis. Não programar automaticamente sem pedido do usuário.
3. Concluir a conferência dos metadados do job existente e registrar a fotografia de quatro CNAEs: importador já adaptado/testado e nova exportação de 368 pares já simulada sem escrita. Depois decidir a carga local e conferir os recortes separados. Não repetir BigQuery nem usar o instante da coleta antiga.
4. Confirmar no job existente que os quatro CNAEs pertencem à mesma consulta e registrar seu instante; não atribuir o instante/hash da coleta antiga a dados novos. Preservar o lote atual como histórico. A exportação já disponível evita repetir a aquisição.
5. Investigar o indicador municipal de renda e compatibilidade temporal; depois experimentar normalização, pesos e justificativas com dados adequados.
6. Revisar RN-006/RN-010/RN-017, matriz e eventual decisão versionada quando as escolhas forem aceitas. Até lá, a proposta não substitui as regras existentes.

## Referências consultadas

- [CONCLA/IBGE — notas explicativas CNAE-Subclasses 2.2](https://concla.ibge.gov.br/images/concla/downloads/cnae-subclasses-2-2-notas-explicativas.pdf): distinção das padarias, corroborada pelos códigos/nomes no catálogo local. A abertura direta retornou 403; os trechos relevantes foram encontrados no índice de busca. Isso não comprova a versão do catálogo local.
- [Portal do Empreendedor — ocupação de barbeiro](https://www.gov.br/empresas-e-negocios/pt-br/empreendedor/quero-ser-mei/atividades-permitidas/b): associação ao CNAE 9602-5/01. Usado somente para corroborar a associação, não para restringir o piloto a MEIs ou orientar enquadramento tributário.
- [IBGE — Censo 2022, Trabalho e Rendimentos](https://www.ibge.gov.br/estatisticas/Sociais/populacao/22827-censo-demografico-2022.html?edicao=44663): variável de rendimento domiciliar per capita e resultados municipais identificados nos trechos indexados; abertura direta retornou 403. Nenhuma tabela/API ou carga foi validada nesta proposta.
- [Comissão Europeia/JRC — ponderação](https://knowledge-for-policy.ec.europa.eu/composite-indicators/toolkit_en/navigation-page/10-step-guide_en/step-6-weighting_en): necessidade de tornar pesos explícitos e evitar contagem dupla de indicadores; pesos iguais não são demonstração de causalidade.
- [Comissão Europeia/JRC — sensibilidade](https://knowledge-for-policy.ec.europa.eu/composite-indicators/toolkit_en/navigation-page/10-step-guide_en/step-8-sensitivity-analysis_en): avaliar o efeito das escolhas sobre scores e posições. Essas referências apoiam o método de construção, não validam os perfis ou a hipótese comercial do RightPoint.
