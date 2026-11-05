# Planos

Esta pasta armazenará planos de mudanças que precisem continuar por várias etapas ou sessões.

## Próximas etapas da modelagem

1. validar as regras de negócio com o grupo;
2. resolver os pontos marcados como `A CONFIRMAR`;
3. revisar as restrições de integridade;
4. validar classes, atributos, métodos e cardinalidades;
5. consolidar o documento final exigido pela atividade.

## Continuidade da implementação

O backend utiliza o projeto Laravel em `Right_Point/`. Município e CNAE foram escolhidos para a primeira versão; os demais detalhes do escopo ainda precisam ser validados.

Consulte o [plano de implementação inicial](./implementacao-inicial.md) antes de retomar o desenvolvimento. Ele complementa as etapas de modelagem acima.

- [Análise territorial com H3](./analise-territorial-h3.md): proposta de evolução intramunicipal, requisitos de dados, limites e etapas de avaliação; ainda não implementada.
- [Referências temporais no diagnóstico e no score](./compatibilidade-temporal-diagnostico.md): revisão v0.2, seleção/elegibilidade técnica implementadas e premissa explícita CNPJ 2026, com evolução/histórico pendentes.
- [Score piloto v1 — decisão e guia de estudo](../decisoes-arquiteturais/001-score-piloto-rj.md): fórmula 50/50 implementada, fontes, exemplos reais, sensibilidade e próximos passos; sem validação comercial ou aprovação acadêmica definitiva.
