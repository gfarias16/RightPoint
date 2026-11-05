# Decisão 001 — score experimental municipal do RJ

Data: 04/11/2026. Método implementado: `piloto-rj-censo2022-cnpj2026-v1`.

**Situação:** usuário autorizou prosseguir com diagnóstico/score e assumir CNPJ como 2026. Implementada a proposta inicial 50/50 como experimento acadêmico. Isso não equivale a aprovação definitiva do grupo/professora nem a validação de poder preditivo comercial.

## Contexto e alternativas

O banco já contém 92 populações, 92 rendas e a fotografia CNPJ dos quatro CNAEs nos 92 municípios. A renda e a população são de 2022; a consulta CNPJ ocorreu em 03/11/2026, com competência e revisão editorial não confirmadas.

Alternativas consideradas:

1. Manter somente diagnóstico descritivo até comprovar a competência CNPJ: preserva uma exigência mais rigorosa, mas não permite experimentar a nota agora.
2. Usar 2026 como premissa explícita para o lote conferido e gerar índice experimental: decisão atual do usuário.
3. Gravar 2026 como competência confirmada na fonte: rejeitada, pois alteraria a procedência sem evidência.

Manter população Censo 2022 nesta versão preserva a base já importada e uma referência comum com a renda. População estimada 2026 foi pesquisada, mas não integrada. Trocá-la exige nova versão e avaliação do efeito sobre a razão e a nota. Não atualizar renda por inflação ou por crescimento presumido nesta versão.

## Decisão temporal e conjunto de dados

| Entrada | Recorte fixado | Interpretação |
| --- | --- | --- |
| População | Fonte `ibge-censo-2022-populacao`, indicador `populacao_residente`, Pessoas, 2022, agregado 4714/variável 93 | Total residente do Censo; denominador, sem bônus separado na fórmula |
| Renda | Fonte `ibge-censo-2022-renda-domiciliar`, indicador `rendimento_domiciliar_per_capita_medio`, Reais, 2022, tabela 10295/variável 13431/totais | Média nominal mensal domiciliar por pessoa, julho/2022, amostra; aproximação de contexto, não salário ou gasto com a atividade |
| CNPJ | Fonte `opencnpj-receita`, critério `ativos_cnae_principal_exato_v1`, consulta `2026-11-03 20:41:41` UTC | Registro ativo/CNAE principal exato; 2026 é premissa analítica, não confirmação da competência pela fonte |

Hash SHA-256 do arquivo CNPJ, normalizado para LF: `c6f18525e52c489384b8feaa9c6e700ccdf60acef3ee1b682facece2db6ce3b1`. A premissa aplica-se **somente a essa fotografia**, não automaticamente a qualquer coleta feita em 2026. `periodo_referencia` e `versao_fonte` originais continuam NULL. Não altera importadores ou registros existentes.

O conjunto é fixado pelos 92 códigos IBGE do RJ no [mapeamento conferido](../../Right_Point/config/municipios_receita.php). Cada análise compara um único CNAE: `5611201`, `1091102`, `4721102` ou `9602501`. Padarias permanecem separadas por subclasse; `9602501` inclui cabeleireiros/manicure/pedicure e não isola barbearias. A escala de notas é relativa ao CNAE; não serve para escolher entre atividades diferentes.

## Como calcular e explicar

1. Calcular para cada município a oferta registrada: `contagem / população × 10.000`.
2. Comparar a renda do município com as outras 91 cidades. Valores maiores recebem posição maior.
3. Comparar a razão de oferta com o mesmo CNAE nas outras cidades. Valores menores recebem mais pontos no componente **menor oferta**.
4. Converter posições em pontos de 0 a 100, com empates pela posição média.
5. Combinar metade de cada componente, arredondando a nota final para uma casa decimal.

```text
posição média = quantidade de valores menores + (quantidade de iguais + 1) / 2
pontos de renda = 100 × (posição média da renda − 1) / (N − 1)
pontos de menor oferta = 100 − 100 × (posição média da oferta − 1) / (N − 1)
score = 0,50 × pontos de renda + 0,50 × pontos de menor oferta
N = 92
```

São **pontos de posição relativa**, não porcentagens de consumidores ou probabilidade. Se todos os valores de um fator forem iguais, ele dá 50 pontos a todos e não diferencia cidades. O cálculo usa valores sem arredondamento prévio; só a apresentação arredonda razão e componentes. O serviço matemático aceita N > 1 para testes; a elegibilidade da aplicação exige os 92 municípios.

A escala de 10 mil facilita ler a razão. Multiplicar todas as razões pelo mesmo fator positivo não altera suas posições e, portanto, não altera este score. Não há um limite comercial de “10 mil pessoas”.

Os pesos iguais foram escolhidos como hipótese inicial simples, sem evidência para priorizar uma dimensão. A hipótese de maior renda e menor oferta favoráveis à entrada precisa ser confrontada com pesquisa de mercado. Menos estabelecimentos também pode refletir pouca demanda; renda alta pode ser inadequada ao público/preço pretendido. A normalização por posição comprime distâncias: pequenas diferenças de valor podem separar posições, e grandes diferenças não geram bônus proporcional.

## Exemplo real conferido em 04/11

Restaurantes, CNAE `5611201`, com os dados locais atuais:

| Município | Renda nominal de 2022 | Contagem | População 2022 | Razão por 10 mil | Pontos de renda | Pontos de menor oferta | Score |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Maricá | R$ 1.707,99 | 620 | 197.277 | 31,43 | 89,0110 | 10,9890 | **50,0** |
| Barra Mansa | R$ 1.407,80 | 308 | 169.894 | 18,13 | 64,8352 | 53,8462 | **59,3** |

Maricá recebe aproximadamente `44,5055 + 5,4945 = 50,0`. Barra Mansa recebe `32,4176 + 26,9231 = 59,3407`, exibido como 59,3. A diferença expressa os fatores e a regra escolhida, não comprova que um restaurante terá mais sucesso em Barra Mansa.

Exercício de sensibilidade, recalculado a partir dos mesmos componentes, sem mudar os dados:

| Pesos renda / menor oferta | Maricá | Barra Mansa |
| --- | ---: | ---: |
| 30% / 70% | 34,4 | 57,1 |
| 50% / 50% — implementado | 50,0 | 59,3 |
| 70% / 30% | 65,6 | 61,5 |

Com 70% para renda, a ordem entre essas duas cidades se inverte. Isso demonstra dependência das escolhas do modelo; não valida nenhum peso. A interface usa somente 50/50. Avaliar sensibilidade dos 92 municípios e perfis, com o grupo, continua próximo passo.

**Avanço posterior em 04/11:** análise completa implementada nos quatro CNAEs/92 municípios, com comando somente de leitura, três cenários, notas/posições, empates médios e deslocamentos em relação à base. [Procedimento e interpretação](../procedimentos/sensibilidade-score.md) e [relatório real](../analises/sensibilidade-score-2026-11-04.md). A revisão dos resultados pelo grupo continua pendente; os pesos da consulta não foram alterados. O conjunto passou a ser preparado em lote por um método compartilhado, preservando a seleção/elegibilidade e o hash da versão existente.

**Apresentação da consulta:** nota/indicadores/fatores e referências curtas ficam na primeira camada. Fórmula, fonte/competência desconhecida e limitações permanecem em detalhes expansíveis. A indicação “Em avaliação” preserva o estágio do modelo, sem narrar decisões internas ou sobrecarregar a leitura. A premissa continua explícita nos detalhes/API/documentação, sem se tornar competência confirmada.

## Integridade e estados

- Falta de população, renda ou contagem no recorte fixado em qualquer município bloqueia o score de todo o conjunto. Retornar quantidade apta e motivo; não excluir cidades silenciosamente.
- População deve ser finita e positiva; renda finita e não negativa; contagem não negativa, nenhum CNPJ ausente e registros do recorte não menores que a contagem distinta.
- Fonte, unidade, período e identificação de recorte precisam coincidir com o método. Não escolher fonte alternativa ou outro ano por município.
- Zero explicitamente registrado é valor válido no experimento; ausência é NULL. Zero não comprova inexistência comercial nem garante oportunidade.
- O controller pode mostrar a coleta CNPJ mais recente; se ela divergir da fotografia fixada, o score fica indisponível. Valores exibidos precisam coincidir com os usados no cálculo, inclusive se uma reimportação atualizar o mesmo ID entre leituras.
- Nota zero deve aparecer como `0,0 / 100`, não ser confundida com indisponibilidade.
- Metadados desconhecidos continuam preservados. A exceção temporal explícita permite **experimento sob premissa**, não resultado confiável com competência comprovada. RN/RI de análise definitiva continuam pendentes de validação.

## Implementação, rastreabilidade e limites

[DiagnosticoPiloto](../../Right_Point/app/Services/DiagnosticoPiloto.php) seleciona o conjunto e verifica elegibilidade; [CalculadorScoreRelativo](../../Right_Point/app/Services/CalculadorScoreRelativo.php) calcula posições/pesos. O [controller](../../Right_Point/app/Http/Controllers/ExplorarController.php) retorna `score` numérico ou NULL e `diagnostico` com estado `experimental`/`indisponivel`, fatores/pesos/contribuições, motivos, método, referências, premissa, conjunto e hash dos dados. Consulta local, sem rede externa, escrita ou migration nova.

O hash do conjunto usa método/CNAE e linhas ordenadas por IBGE: valores populacionais/renda, suas datas de coleta, contagem e registros no recorte. Identifica mudança de dados; **não armazena nem permite recuperar sozinho uma análise antiga**. As consultas são recalculadas no banco atual e não há persistência de análises/exportação de relatório implementada. Antes de oferecer histórico, guardar entradas completas, procedência e resultado por análise, conforme RI-012/RI-013.

Não foram implementados probabilidade de sucesso, categorias alto/médio/baixo, limiar de saturação, fluxo de consumidores, preços/custos, raio, H3 ou concorrentes individuais. Cobertura da base, informais, validade cadastral real e qualidade preditiva continuam limitações. A diferença 2022/2026 é aceita para explorar contexto municipal, não para afirmar renda/demanda atual de 2026.

## Reversibilidade e evolução

Sem alteração de banco: reverter os serviços, integração e assets da etapa remove a nota e preserva os dados. Uma nova fotografia, população estimada, união de CNAEs, normalização ou pesos exigem versão explícita e atualização de testes/documentação; não reaproveitar a premissa desta fotografia silenciosamente.

Próximos passos: revisar os fatores/perfis e sensibilidade com grupo/professora; decidir a população estimada 2026 como novo cenário; depois preparar persistência/exportação com as entradas completas. Validação matemática e teste de tela demonstram funcionamento, não eficácia para prever negócios.

## Fala para a apresentação

> Construímos um índice experimental relativo aos 92 municípios do Rio de Janeiro, dentro da mesma atividade. Metade da nota vem da posição da renda municipal e metade da menor oferta registrada por habitante. Usamos renda e população de 2022 e assumimos 2026 para este lote CNPJ, sem alterar os metadados originais. A tela explica os fatores e essa diferença de anos. A nota compara o contexto disponível; não é porcentagem de sucesso e ainda precisa de revisão e validação comercial.
