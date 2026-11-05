# Referências temporais no diagnóstico e no score

Registrado em 04/11/2026. **Revisão v0.2:** seleção uniforme e elegibilidade técnica implementadas no score experimental; evolução/histórico e validação comercial ainda pendentes. Após o estudo v0.1, o usuário decidiu assumir CNPJ como 2026 e autorizou prosseguir. A [decisão 001](../decisoes-arquiteturais/001-score-piloto-rj.md) registra essa exceção explícita, o método 50/50, exemplos e limites. Não equivale a aprovação definitiva ou confirmação da competência pela fonte.

Referências internas: [critérios piloto](./criterios-diagnostico-piloto.md), [RN-009 a RN-012 e demais regras](../contexto/regras-de-negocio.md), [RI-010 a RI-015](../contexto/restricoes-de-integridade.md), [fontes](../FONTES_DADOS.md) e [estado verificado](../STATUS.md).

## 1. O que queremos representar

O primeiro diagnóstico descreve contexto comercial municipal a partir de fontes públicas. Sua emissão em 2026 não transforma todos os dados usados em medições de 2026. O eventual índice composto será uma comparação relativa sob um método identificado; não é previsão de sucesso ou probabilidade estatística.

Três datas precisam ser distinguidas:

| Informação | Significado | Exemplo do projeto |
| --- | --- | --- |
| Referência do dado | Quando o fenômeno foi medido/estimado | Renda nominal de julho de 2022 |
| Publicação/revisão da fonte | Quando a instituição divulgou/corrigiu o resultado | Pode ser posterior ao ano medido; registrar se conhecida |
| Consulta/importação/análise | Quando acessamos, gravamos ou combinamos os dados | Extração CNPJ em 03/11/2026 não comprova competência novembro/2026 |

Manter referências desconhecidas como desconhecidas. Um indicador conhecido apenas por ano não recebe dia/mês fictícios. A identificação da tabela/variável e o hash do conteúdo ajudam a reproduzir o recorte, mas não são confirmação da revisão editorial oficial.

## 2. Inventário atual e possibilidades

| Fator | Referência e conceito | Estado observado | Limitação temporal |
| --- | --- | --- | --- |
| População residente | Censo 2022, pessoas | 92 municípios carregados | Descreve o Censo; não representa população atual |
| População estimada | Estimativas anuais, referência em 1º de julho; tabela 6579/variável 9324 | 2024/2025/2026 consultados, não importados | Método diferente do Censo; adoção e seleção ainda pendentes |
| Renda municipal | Média nominal mensal domiciliar por pessoa, julho/2022, amostra, tabela 10295/variável 13431 | 92 rendimentos carregados, fonte/unidade/recorte conferidos por leitura | Não é renda atual, salário individual ou gasto na atividade |
| CNPJ agregado | Ativos/CNAE principal exato no município | 368 novos pares carregados; consulta em 03/11/2026; premissa analítica 2026 | Período de referência e versão editorial desconhecidos; premissa não confirma atualidade |
| Renda PNAD 2025 do estado | Rendimento domiciliar estadual, universo/método próprios | Divulgação pesquisada; não integrada | Não substitui renda municipal nem diferencia as 92 cidades |

Renda e população com rótulo 2022 ainda têm universos e conceitos distintos: renda é da amostra e tem exclusões de moradores, enquanto população é total residente. Usar o mesmo ano é desejável quando pertinente, mas não resolve sozinho compatibilidade de universo, território, conceito ou cobertura. Empresas extraídas depois também não passam a representar 2022.

## 3. Proposta de regras temporais

Os identificadores PT organizam a política deste estudo. Fonte/recorte uniforme, conjunto completo, ausência sem zero e premissa específica estão em execução no piloto; histórico e validação definitiva continuam pendentes. Não substituem silenciosamente as RN/RI conceituais.

- **PT-01 — Referências visíveis:** cada fator mostra referência, fonte, natureza (Censo/estimativa/amostra/registro administrativo), consulta e limitações. O resultado declara suas diferentes referências; não se chama simplesmente “score de 2026” porque foi emitido em 2026.
- **PT-02 — Mesmo fator, mesmo recorte entre municípios:** fixar um conjunto antes de calcular. Se o fator população usa estimativa 2026, todos os municípios comparados usam essa edição/ano/conceito. Renda municipal 2022 é comparada com renda municipal 2022 da mesma definição. Não selecionar silenciosamente 2026 para uma cidade e 2022 para outra.
- **PT-03 — Entre fatores, avaliar adequação:** população 2026 e renda 2022 podem descrever dimensões diferentes, com defasagem declarada. Para combiná-las em índice, justificar finalidade, defasagem aceitável e limites da conclusão. Ter valores preenchidos não basta. Não há tolerância universal de “até X anos” adotada neste projeto.
- **PT-04 — Desconhecido exige bloqueio ou cenário explicitamente autorizado:** sem referência ou premissa aceita para o recorte, manter score NULL. Exceção atual: usuário assumiu 2026 para a fotografia CNPJ de `2026-11-03 20:41:41` UTC, identificada por hash; o código admite somente esse experimento e declara `confirmado_pela_fonte=false`. Não preenche metadados originais, não herda a premissa para outras coletas e não apresenta a nota como validada. A captura do job sozinha não é essa autorização nem confirma competência.
- **PT-05 — Ausência e conjunto comparado:** fator obrigatório ausente/inválido, fonte territorial incompatível ou dados fora do conjunto fixado impedem a nota daquele conjunto. Não preencher com zero, substituir pela média estadual ou redistribuir pesos. Para o piloto, propor 92 municípios elegíveis; eventual comparação de subconjunto exige decisão explícita, conjunto publicado e nova identificação, nunca exclusão silenciosa.
- **PT-06 — Contexto separado de fatores:** um dado pode aparecer como informação contextual sem entrar na fórmula. A ausência de contexto opcional não altera pesos. Apenas fatores declarados e elegíveis influenciam o índice e sua justificativa.
- **PT-07 — Qualidade separada do potencial:** defasagem e incerteza afetam a aptidão/interpretação dos dados. Não descontar, por exemplo, 10 pontos do potencial porque a renda é antiga; isso misturaria qualidade da fonte com característica comercial. Não inventar um percentual de confiança estatística.
- **PT-08 — Histórico reproduzível:** mudanças de ano, fonte, conjunto de municípios, normalização ou peso geram nova versão de análise/método. Preservar os valores e referências usados. Uma mudança de nota entre métodos não comprova melhora/piora comercial.

## 4. Condições antes de produzir qualquer índice exibido

| Condição | Se não atendida | Estado atual |
| --- | --- | --- |
| Finalidade, fatores, pesos e versão declarados para experimentar | Mostrar pendência; score NULL | 50/50 implementado por solicitação do usuário; aprovação definitiva pendente |
| Referências ou premissa explícita adequadas ao cenário exploratório | Bloquear nota sem preencher a fonte por suposição | População/renda 2022; CNPJ 2026 assumido somente para o lote definido |
| Unidades, território e conceitos iguais por fator | Bloquear quando divergir; explicar cobertura | Elegibilidade técnica implementada; cobertura comercial independente não validada |
| Conjunto municipal homogêneo e valores válidos | Não usar fallback por cidade | 92 códigos fixos; conjunto parcial bloqueia nota. População estimada não integrada |
| Sensibilidade, hipóteses e utilidade comercial avaliadas | Não apresentar índice como validado ou preditivo | Exemplo de sensibilidade documentado; revisão dos 92 municípios e validação comercial pendentes |

“Experimental” sozinho não resolve entradas desconhecidas. Nesta revisão existe uma decisão explícita de cenário, limitada ao lote registrado, com premissa visível na tela/API. Sua nota continua exploratória; não deve ser apresentada como orientação comercial validada. Validação acadêmica e validação empírica de utilidade comercial são coisas distintas.

## 5. Como a seleção e o cálculo deverão funcionar

Fluxo planejado:

```text
Escolha município + atividade/perfil + finalidade
→ Escolha um conjunto de dados identificado para a comparação
→ Fixe fonte, conceito e referência de cada fator para todos os municípios
→ Confira integridade, cobertura e aptidão temporal
→ Se faltar condição obrigatória: indicadores + motivos + score NULL
→ Se todas forem atendidas: normalização + pesos do método aprovado
→ Grave fatores/referências/conjunto/método/resultado, se houver histórico
→ Exiba resultado com ficha de fontes e limitações
```

O serviço atual seleciona os 92 municípios e recortes fixados, calcula o índice relativo e retorna método/fatores/pesos/referências/premissa/hash. Não toma automaticamente a coleta mais recente de cada cidade para formar o conjunto. Se os dados exibidos divergirem do recorte fixado, bloqueia o score. A consulta continua sem persistência de análises: o hash identifica entradas, mas não recupera valores antigos sozinho.

Planejar armazenamento por análise dos valores originais e transformados, referências com sua precisão (ano/mês/dia/desconhecido), decisão temporal e justificativa, conjunto comparado e versão da fórmula. Se houver ajuste monetário, guardar também índice/base de preços/fator de ajuste, sem sobrescrever o valor original. São requisitos de evolução, não colunas já criadas.

## 6. Exemplo: mudar o denominador muda a razão

Exercício aritmético com as mesmas contagens de restaurantes, sem nova importação ou alteração da tela:

| Município | Contagem mantida | População Censo 2022 | Razão por 10 mil | Estimativa 2026 consultada | Razão do cenário 2026 |
| --- | ---: | ---: | ---: | ---: | ---: |
| Barra Mansa | 308 | 169.894 | 18,13 | 181.675 | 16,95 |
| Maricá | 620 | 197.277 | 31,43 | 212.703 | 29,15 |

A contagem não mudou neste exercício; mudou apenas a referência populacional. Portanto, a diferença da razão não demonstra fechamento de empresas ou melhora da oportunidade. As duas combinações continuam exploratórias porque a referência da base CNPJ é desconhecida. O sistema atual ainda usa população de 2022; a coluna de cenário não é resultado já implementado.

## 7. Atualização monetária e teste de sensibilidade

Corrigir renda nominal de 2022 por inflação para uma data posterior expressaria aquele valor em outra base de preços. Não descobriria salários, distribuição de renda, composição familiar ou renda efetivamente recebida em 2026. Esse cálculo só poderia aparecer como estimativa de equivalência monetária identificada, com índice e período documentados, sem trocar o rótulo do dado observado.

Na hipótese de normalização por posição percentual, multiplicar a renda de todos os municípios pelo mesmo fator positivo preserva a ordenação. Isso não acrescenta evidência sobre a renda municipal atual. Não aplicar correção ou crescimento presumido automaticamente para fazer os anos “baterem”.

Antes do eventual índice, preparar cenários que mudem uma escolha por vez: população Censo versus estimativa; inclusão/exclusão de um fator previamente definido como opcional; pesos 30/70, 50/50 e 70/30 da proposta; método de normalização. Manter fixas as demais entradas e registrar diferenças. Comparar posições somente no mesmo conjunto de municípios. Mudança grande exige discutir estabilidade e interpretação, sem inventar agora um limiar numérico. Esses experimentos não comprovam poder preditivo ou sucesso comercial.

## 8. O que aparecerá na interface — planejado

- Ficha de referências: por exemplo, população estimada 2026, renda municipal julho/2022, CNPJ com referência desconhecida e extração 03/11/2026.
- Aviso de referências distintas com linguagem visível, sem depender apenas de cor ou de tooltip.
- Explicação do efeito: renda descreve contexto de 2022; população estimada muda o denominador; CNPJ não tem competência confirmada.
- Estado explícito do score e lista das condições pendentes, não apenas um aviso genérico sob uma nota.
- Se houver índice elegível futuramente, mostrar fatores, pesos, conjunto comparado, versão e limitações. Data de emissão separada das referências dos dados.

Implementado: fontes/anos, avisos, razão exploratória, score experimental ou NULL com motivo, fatores/pesos, método, conjunto fixado e premissa. Histórico/exportação e população estimada continuam pendentes. A premissa não transforma dados desconhecidos em referência comprovada.

## 9. Sequência de implementação e validação

1. Revisar esta proposta com usuário/grupo e registrar as escolhas aceitas sem declará-las exigência da professora.
2. Definir finalidade e papel de cada fator: contexto ou componente obrigatório/opcional do método.
3. Renda carregada/conferida e Censo 2022 mantido na versão atual. Decidir população estimada como novo cenário, preservando o Censo.
4. Premissa CNPJ 2026 autorizada para o lote específico, sem alterar metadados. Continuar investigação de competência/versão/cobertura antes de chamar o resultado de validado.
5. Política experimental registrada na decisão 001; novas referências/coletas exigem revisão explícita, sem limiar geral arbitrário.
6. Seleção/elegibilidade implementadas e testadas: ano, fonte, unidade, versão, fator ausente/inválido, zero, conjunto incompleto e coleta diferente.
7. Método 50/50 implementado, testes de posições/empates/pesos e exemplo de sensibilidade documentado. Análise dos 92 municípios/quatro CNAEs implementada e executada: [procedimento](../procedimentos/sensibilidade-score.md). Revisão dos resultados pelo grupo e validação comercial pendentes.
8. Integrar ficha/justificativa/histórico e testar que atualização das fontes não reescreve análises antigas.

## 10. Texto de apoio para a apresentação

> O RightPoint usa fontes públicas com calendários diferentes. Nesta versão, população e renda são de 2022 e assumimos 2026 para o lote CNPJ, sem afirmar confirmação pela fonte. Comparamos os 92 municípios com o mesmo recorte por fator e mostramos as referências e a premissa. A nota é um índice experimental de contexto, calculado com renda relativa e menor oferta registrada por habitante. Não é uma fotografia integral de 2026 nem porcentagem de sucesso.

Para explicar o que já foi feito: cargas verificadas, indicador descritivo, elegibilidade técnica, índice experimental, fatores e avisos estão implementados. População estimada na consulta, histórico/exportação e validação comercial são próximos trabalhos.

## Referências metodológicas

- [JRC/Comissão Europeia — seleção dos dados](https://knowledge-for-policy.ec.europa.eu/composite-indicators/toolkit_en/navigation-page/10-step-guide_en/step-2-data-selection_en): discute comparabilidade e mistura de anos; mudanças de ranking também podem decorrer de cobertura/método.
- [JRC/Comissão Europeia — sensibilidade](https://knowledge-for-policy.ec.europa.eu/composite-indicators/toolkit_en/navigation-page/10-step-guide_en/step-8-sensitivity-analysis_en): fundamenta examinar efeito de hipóteses, pesos e normalização. Não valida automaticamente o caso comercial do RightPoint.
- [OECD/UE/JRC — manual de indicadores compostos](https://www.oecd.org/en/publications/handbook-on-constructing-composite-indicators-methodology-and-user-guide_9789264043466-en.html): referência geral de construção e transparência metodológica. As regras PT e os bloqueios deste documento são propostas do projeto, não uma norma externa copiada.
- Referências empíricas/URLs dos indicadores: [FONTES_DADOS](../FONTES_DADOS.md) e [procedimento da renda](../procedimentos/renda-municipal-rj.md).
