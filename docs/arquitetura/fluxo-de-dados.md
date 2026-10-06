# Fluxo de Dados - RightPoint

Este é o fluxo conceitual de uma consulta. A [matriz e o fluxograma detalhado](./matriz-dados-diagnostico.md) distinguem a atualização agendada, de uma fonte por vez, da consulta aos dados locais publicados. Nada disso está implementado ainda.

## Fluxo principal da análise

```mermaid
sequenceDiagram
    actor U as Empreendedor
    participant I as Interface
    participant A as Orquestrador da análise
    participant D as Dados locais publicados
    participant M as Motor de potencial
    participant H as Histórico

    U->>I: Escolhe município RJ no mapa/busca e CNAE
    I->>A: Solicita análise com código IBGE e CNAE
    A->>A: Valida parâmetros
    A->>D: Consulta indicadores, cobertura e empresas já importados
    D-->>A: Retorna dados com fonte, período e atualização
    alt População e cobertura CNPJ suficientes; fórmula aprovada
        A->>A: Conta ativos do município com CNAE principal exato
        A->>M: Envia somente fatores aprovados e pertinentes
        M-->>A: Retorna fatores, score e justificativa
        opt Histórico aprovado e habilitado
            A->>H: Salva resultado e snapshot dos dados
        end
    else Dados mínimos ou fórmula indisponíveis
        A->>A: Prepara motivo da insuficiência; não gera score
    end
    A-->>I: Retorna dados disponíveis, resultado ou limitações
    I-->>U: Exibe resultado e limitações
```

## Transformações dos dados

1. **Entrada:** atividade e referência geográfica informadas pelo usuário.
2. **Identificação:** atividade por CNAE e município do RJ por código IBGE, após busca por nome ou seleção no mapa.
3. **Leitura local:** obtenção de indicadores e estabelecimentos das versões já importadas; a coleta e a normalização das fontes ocorrem antes, na atualização agendada.
4. **Qualidade:** verificação de população, cobertura CNPJ e validade temporal da versão; prazo de validade `A CONFIRMAR`.
5. **Concorrência:** proposta inicial de contagem por município, situação ativa e CNAE principal exato, desde que a cobertura seja conhecida.
6. **Pontuação condicional:** cálculo somente com fórmula/pesos aprovados e dados mínimos disponíveis; sem eles, exibir insuficiência sem score.
7. **Explicação:** justificar apenas fatores efetivamente usados e apresentar fontes, períodos e datas.
8. **Persistência:** preservar resultado e snapshot quando o histórico for aprovado e habilitado.

## Dados que exigem rastreabilidade

- fonte e data de referência de cada indicador;
- código IBGE, versão da fonte e data da atualização local;
- versão ou data da base de estabelecimentos;
- critérios usados para reconhecer concorrentes;
- pesos e versão da fórmula de score;
- fatores citados na justificativa;
- data e parâmetros da análise.

## Falhas previstas

| Situação | Comportamento esperado |
| --- | --- |
| Atividade não reconhecida | Solicitar correção ou seleção de atividade válida. |
| Região ou localização inválida | Interromper a análise e informar o campo inconsistente. |
| Fonte externa indisponível durante atualização | Manter última versão válida, identificar sua data e a falha; não presumir que ela ainda atende ao prazo de validade. |
| Dados mínimos, cobertura ou fórmula insuficientes | Informar a limitação e não apresentar score como validado, conforme `RN-017`. |
| Nenhum concorrente encontrado | Exibir zero somente quando a cobertura da base tiver sido verificada para município e CNAE. |
| Estabelecimento sem localização | Excluí-lo da análise por raio ou tratá-lo conforme regra ainda `A CONFIRMAR`. |
