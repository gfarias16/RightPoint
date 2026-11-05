# Maricá e Barra Mansa — leitura dos pesos do diagnóstico

Estudo de 05/11/2026, preparado pelo agente a pedido do usuário. Base: [relatório de sensibilidade gerado em 04/11](./sensibilidade-score-2026-11-04.md), método `piloto-rj-censo2022-cnpj2026-v1`. Não houve nova aquisição, consulta ao banco, mudança de fórmula ou escolha de pesos definitivos nesta etapa.

## 1. O que estamos avaliando

Queremos entender por que o método dá notas diferentes e quanto a comparação depende dos pesos. O objetivo não é escolher um peso para favorecer uma cidade.

Cada cenário compara os mesmos 92 municípios do RJ dentro de um único CNAE. População e renda são do Censo 2022; o lote CNPJ é assumido como 2026 pelo projeto, sem confirmação da competência pela fonte. Essas limitações não desaparecem ao mudar os pesos.

Os dois fatores são pontos de posição relativa: renda maior recebe mais pontos de renda; oferta registrada menor por habitante recebe mais pontos de menor oferta. Nenhum dos dois mede diretamente procura, faturamento, lucro ou sobrevivência de um negócio. [Fórmula, fontes e premissas](../decisoes-arquiteturais/001-score-piloto-rj.md).

## 2. Restaurantes: por que as notas diferem

Valores registrados na decisão 001 para o CNAE `5611201`:

| Entrada | Maricá | Barra Mansa |
| --- | ---: | ---: |
| População residente, Censo 2022 | 197.277 | 169.894 |
| Renda nominal média mensal domiciliar por pessoa, 2022 | R$ 1.707,99 | R$ 1.407,80 |
| Estabelecimentos ativos/CNAE principal exato no lote | 620 | 308 |
| Oferta registrada por 10 mil habitantes | 31,43 | 18,13 |
| Pontos de renda relativa | 89,0110 | 64,8352 |
| Pontos de menor oferta relativa | 10,9890 | 53,8462 |

Maricá tem renda maior e mais estabelecimentos registrados por habitante. A primeira característica aumenta seu componente de renda; a segunda reduz seu componente de **menor oferta**. Barra Mansa tem renda menor, mas recebe mais pontos no segundo componente.

Com os pesos atuais, Maricá soma aproximadamente `44,5055 + 5,4945 = 50,0`. Barra Mansa soma `32,4176 + 26,9231 = 59,3407`, apresentada como `59,3`. As contribuições usam os pontos já apresentados com quatro casas; o serviço calcula com precisão anterior ao arredondamento visual.

Isso explica o resultado da regra, não comprova que abrir um restaurante em Barra Mansa seja melhor. Uma oferta menor pode estar associada a espaço para entrada ou a procura menor; esta base não distingue essas hipóteses. Da mesma forma, mais estabelecimentos não comprova saturação.

## 3. O que muda nos três cenários

Somente os pesos mudam. Menor número de posição significa posição mais alta entre os 92 municípios; não é uma classificação de sucesso comercial.

| Renda / menor oferta | Maricá: nota | Maricá: posição | Barra Mansa: nota | Barra Mansa: posição |
| --- | ---: | ---: | ---: | ---: |
| 30/70 | 34,4 | 76 | 57,1 | 31 |
| 50/50 — aplicação atual | 50,0 | 45 | 59,3 | 22 |
| 70/30 | 65,6 | 17 | 61,5 | 29 |

Duas conclusões diferentes precisam ficar claras:

- **A ordem entre as cidades se inverte.** Barra Mansa tem nota maior nos cenários 30/70 e 50/50; Maricá passa à frente em 70/30. A escolha dos pesos influencia a conclusão sobre esses dois fatores.
- **Nota e posição não são a mesma coisa.** De 50/50 para 70/30, a nota de Barra Mansa sobe de 59,3 para 61,5, mas sua posição cai de 22 para 29. Outros municípios recebem aumentos maiores. Nenhum indicador econômico mudou; mudou a regra aplicada ao conjunto.

Entre 30/70 e 70/30, Maricá varia 31,2 pontos e 59 posições; Barra Mansa varia 4,4 pontos e 9 posições. Essa é a amplitude dos três cenários, não um intervalo de confiança. Não foi estabelecido um limite de estabilidade ou uma classe de alto/baixo potencial.

Os pontos de renda de Maricá não representam 89% de chance de sucesso. A nota 50,0 também não significa que metade dos negócios terá sucesso.

## 4. Os outros CNAEs mostram o mesmo comportamento?

Notas extraídas do mesmo relatório. Cada par deve ser comparado **somente dentro de seu CNAE**; a escala relativa não permite escolher entre atividades diferentes.

| CNAE | Município | Nota 30/70 | Nota 50/50 | Nota 70/30 |
| --- | --- | ---: | ---: | ---: |
| 1091102 — padarias, produção própria | Maricá | 26,7 | 44,5 | 62,3 |
| 1091102 — padarias, produção própria | Barra Mansa | 45,6 | 51,1 | 56,6 |
| 4721102 — padarias, revenda | Maricá | 52,1 | 62,6 | 73,2 |
| 4721102 — padarias, revenda | Barra Mansa | 28,7 | 39,0 | 49,3 |
| 9602501 — cabeleireiros/manicure/pedicure | Maricá | 26,7 | 44,5 | 62,3 |
| 9602501 — cabeleireiros/manicure/pedicure | Barra Mansa | 24,1 | 35,7 | 47,4 |

Em produção própria, a ordem também se inverte no cenário 70/30. Em revenda e no grupo de cabeleireiros/manicure/pedicure, Maricá mantém nota maior nos três cenários. Isso não torna essas comparações comercialmente validadas nem demonstra estabilidade em todos os pesos possíveis.

Padarias continuam em duas subclasses separadas. O CNAE `9602501` não isola barbearias. A renda de uma cidade é a mesma entre esses cálculos, mas o componente de oferta muda com o CNAE. Portanto, duas notas iguais em atividades diferentes não significam o mesmo contexto ou posição.

Esta leitura detalha duas cidades. As mudanças nos demais municípios estão no relatório completo; não generalizar a comparação local como se todas as cidades se comportassem da mesma forma.

## 5. O que precisamos decidir com o grupo/professora

| Questão | Limite observado | Encaminhamento para discussão |
| --- | --- | --- |
| Renda maior é favorável a qual perfil de cliente e faixa de preço? | Média municipal não descreve todos os consumidores nem gasto com a atividade. | Definir público, produto e faixa de preço do perfil antes de interpretar o fator como capacidade de consumo. |
| Menor oferta registrada representa espaço de entrada ou pouca procura? | Contagem e população residente não medem demanda, turismo, circulação ou capacidade de atendimento. | Tratar menor oferta como hipótese; buscar evidência de demanda para sustentá-la ou revisar seu sentido em uma nova versão. |
| Existe justificativa para priorizar um fator? | A ordem pode mudar com 30/70, 50/50 e 70/30. | Registrar a justificativa dos pesos; não escolher a combinação que apenas favorece o resultado esperado. |
| Como avaliar se o diagnóstico é útil? | Testes atuais verificam matemática e integridade, não resultados comerciais. | Definir um resultado observável, período e dados apropriados para avaliação posterior; não converter a nota em probabilidade sem validação específica. |

São perguntas abertas, não decisões aprovadas nesta análise. Não há evidência suficiente aqui para afirmar que 30/70 ou 70/30 seja superior ao 50/50.

## 6. Recomendação e próximo passo

Manter o método atual identificado como **experimental** durante essa discussão, sem alterar os pesos nesta etapa. A nota deve ser lida com seus dois componentes e referências, não isoladamente como recomendação de localização. A sensibilidade não escolhe automaticamente um modelo mais correto.

Após a revisão metodológica, o próximo estudo técnico proposto é comparar a população Censo 2022 com uma estimativa municipal de 2026, em cenário próprio e com fonte/edição/cobertura verificadas. Preservar os dados atuais, aplicar o mesmo cenário aos 92 municípios e medir efeito na razão, nos pontos e nas posições antes de considerar adoção. Não basta trocar o denominador apenas nessas duas cidades: seus pontos dependem das demais 90.

População mais recente não resolve renda antiga, competência CNPJ desconhecida, ausência de demanda ou a justificativa dos pesos. Histórico/exportação com entradas completas e H3 continuam evoluções separadas, não entregas desta análise.

## 7. Conferência desta etapa

As oito linhas município/CNAE e seus 24 scores foram extraídos do relatório versionado. As seis posições de restaurantes e as duas amplitudes foram conferidas contra o mesmo arquivo; as contribuições e notas de restaurantes foram recalculadas com os componentes documentados. Não foi gerado um novo snapshot ou relatório de banco. O relatório original foi preservado.

Não houve implementação, nova carga, alteração de score, acesso externo, teste de navegador ou repetição da suíte completa nesta etapa documental. A última suíte de código continua sendo a registrada em 05/11: 227 testes e 1.208 asserções, conforme [status](../STATUS.md).
