# Análise de sensibilidade dos pesos do score

Implementada em 04/11/2026. Objetivo: estudar quanto a nota e a posição municipal dependem dos pesos escolhidos, mantendo os mesmos dados. O procedimento não escolhe pesos definitivos nem demonstra sucesso comercial.

## O que é comparado

O comando `diagnostico:analisar-sensibilidade` usa os mesmos recortes e verificações de [DiagnosticoPiloto](../../Right_Point/app/Services/DiagnosticoPiloto.php), compartilhados com a consulta da aplicação. Cada CNAE tem os mesmos 92 municípios, renda/população 2022 e fotografia CNPJ definida no método `piloto-rj-censo2022-cnpj2026-v1`. A premissa CNPJ 2026 e os metadados originais desconhecidos continuam preservados. Ver [decisão 001](../decisoes-arquiteturais/001-score-piloto-rj.md).

| Cenário | Renda relativa | Menor oferta registrada relativa | Situação |
| --- | ---: | ---: | --- |
| 30/70 | 30% | 70% | Experimento de sensibilidade |
| 50/50 | 50% | 50% | Base usada pela aplicação |
| 70/30 | 70% | 30% | Experimento de sensibilidade |

Os fatores são normalizados por posição percentual antes da ponderação. O comando usa os fatores originais sem arredondamento visual; não recompõe a nota a partir dos pontos arredondados na tela. O calculador permite outros pesos inteiros de 0 a 100 para testes internos, mas a consulta não aceita peso informado pelo usuário e permanece 50/50.

## Como executar

Dentro de [Right_Point/](../../Right_Point/), após conferir banco/migrations/dados locais:

```powershell
php artisan diagnostico:analisar-sensibilidade
```

O padrão mostra um resumo por CNAE. Para emitir todas as notas/posições em Markdown:

```powershell
php artisan diagnostico:analisar-sensibilidade --markdown --no-ansi
```

A opção emite texto no terminal e **não escreve arquivos**. Ao salvar esse texto como relatório, confira o código de saída antes; falha não deve virar um relatório aparentemente concluído. O comando valida os quatro conjuntos antes de publicar resultados: um CNAE incompleto bloqueia a saída completa, sem publicar somente os outros três.

Sem consulta externa, BigQuery, gravação no banco, migration ou mudança dos pesos da aplicação. Não repetir importações para rodar a análise. Outra máquina pode bloquear por falta de dados locais mesmo com o código atualizado pelo Git.

## Como interpretar

- **Nota:** média ponderada dos dois componentes, exibida com uma casa.
- **Posição:** ordenação decrescente pela nota sem arredondamento visual; valores iguais recebem posição média. Por isso pode existir posição 12,5. Internamente, dez casas removem apenas ruído de ponto flutuante; o conjunto/pesos desta análise têm passos muito maiores que essa precisão.
- **Posições alteradas:** quantidade de municípios cuja posição difere da base 50/50 naquele cenário.
- **Maior deslocamento da base:** maior diferença absoluta de posição entre aquele cenário e 50/50.
- **Deslocamento médio da base:** média dessas diferenças absolutas nos 92 municípios.
- **Amplitude:** diferença entre maior e menor nota/posição nos três cenários; não é o mesmo que maior deslocamento em relação à base.

As linhas completas priorizam maior amplitude de posições; IBGE desempata somente a apresentação. Nome/código não desempata o ranking. O caso em que todos os fatores são iguais mantém notas 50 e posições médias iguais nos três cenários.

Mudança de posição não significa crescimento econômico, abertura/fechamento de empresas ou piora real. Os dados não mudaram; mudou a regra. Não foi definido limiar numérico de estabilidade, alto/baixo potencial ou confiança.

## Resultado real de 04/11/2026

Relatório completo, com 368 municípios/CNAEs e três cenários para cada um: [sensibilidade dos pesos](../analises/sensibilidade-score-2026-11-04.md).

| CNAE | Posições alteradas em 30/70 | Maior deslocamento da base | Posições alteradas em 70/30 | Maior deslocamento da base |
| --- | ---: | ---: | ---: | ---: |
| 5611201 — restaurantes | 90 / 92 | 39 | 87 / 92 | 36,5 |
| 1091102 — padarias, produção própria | 89 / 92 | 27,5 | 87 / 92 | 29 |
| 4721102 — padarias, revenda | 91 / 92 | 33,5 | 90 / 92 | 30 |
| 9602501 — cabeleireiros/manicure/pedicure | 91 / 92 | 36 | 89 / 92 | 36 |

Os resultados mostram dependência da escolha dos pesos. Não permitem concluir que 30/70, 50/50 ou 70/30 é o mais correto: isso exige justificar a relação dos fatores com o tipo de negócio e confrontar o método com evidência. A aplicação permanece 50/50 por decisão de manter a hipótese inicial durante o estudo; não foi selecionada automaticamente a combinação que favorece determinada cidade.

Indicadores e coletas foram comparados antes/depois do comando real e permaneceram iguais. Hashes dos quatro conjuntos coincidiram com os registrados na etapa anterior. O relatório contém resultados/metadados, não todas as entradas necessárias para restaurar uma análise histórica.

## Validação e próximo passo

Testes de matemática, empates, ordem, pesos, integridade e comando usam dados fictícios/SQLite em memória. Incluem conjunto incompleto no último CNAE, relatório com 92 linhas por atividade e preservação do banco/API. A execução real foi somente de leitura. Testes não comprovam eficácia comercial.

Próximo passo: revisar os perfis e a interpretação de renda/menor oferta com grupo/professora, usando este relatório. Depois avaliar população estimada 2026 como cenário separado e definir persistência/exportação de análises completas. União de padarias e barbearias exclusivas continuam questões próprias, não resolvidas por mudar pesos.
