# Limitações Conhecidas - RightPoint

## Estado da modelagem

O RightPoint está em fase de definição do domínio, com a base Laravel em `Right_Point/`. O recorte da primeira versão foi definido como os 92 municípios do RJ, mas ainda não existem fluxo de consulta, mapa, importação de fontes públicas ou fórmula validada para o score. Na escola, as migrations padrão do Laravel, de municípios, de atividades econômicas e de fontes de dados foram aplicadas ao banco local. As demais estruturas do domínio seguem pendentes. Consulte a [matriz de dados e o fluxograma](../arquitetura/matriz-dados-diagnostico.md) e o [plano de continuidade](../planos/implementacao-inicial.md).

## Limitações funcionais

| Limitação | Impacto | Tratamento atual |
| --- | --- | --- |
| Fórmula e pesos do score indefinidos | Ainda não é possível reproduzir um cálculo real. | `A CONFIRMAR` com o grupo. |
| Fontes candidatas indicadas, ainda não validadas para integração | Disponibilidade, cobertura, atualização, licença e formato da maioria dos dados ainda são desconhecidos. | Conferir cada fonte em [FONTES_DADOS](../FONTES_DADOS.md) e manter a arquitetura independente de um fornecedor específico. |
| Equivalência entre atividades indefinida | A contagem de concorrentes pode variar conforme o critério. | Registrar como regra pendente. |
| Cobertura dos 92 municípios ainda não carregada | O recorte RJ está definido, mas só houve teste de população para o município do Rio de Janeiro. | Conferir cobertura completa e associação por código IBGE antes do diagnóstico. |
| Critério inicial de concorrência ainda não validado academicamente | CNAE principal exato e situação ativa são propostas que podem subestimar concorrência. | Revisar com grupo/professora; não contar inativos como ativos nem ausência de dados como zero. |
| Agenda e validade dos dados ainda indefinidas | Uma versão local pode ficar defasada após falha de atualização. | Planejar uma fonte por vez; frequência, retentativas e prazo de validade `A CONFIRMAR`. |
| Qualidade dos dados externos | Dados públicos podem estar ausentes, desatualizados ou sem coordenadas. | Informar insuficiência de dados na análise. |
| Histórico ainda opcional | A persistência necessária pode mudar. | Modelo prevê rastreabilidade sem torná-la decisão final. |
| Perfil do negócio e cadastro de usuário ainda não implementados | O CRUD futuro de atividade/CNAE, município e preferências não define por si só autenticação ou propriedade das análises. | Projetar após a base de dados; campos adicionais e login `A CONFIRMAR`. |

## Limitações do resultado

- o score é uma estimativa baseada nos dados disponíveis, não uma probabilidade estatística;
- fatores como qualidade do produto, gestão, preço, imóvel e fluxo local podem não estar representados;
- correlação entre indicadores e sucesso comercial não significa causalidade;
- o resultado não substitui pesquisa de mercado, análise financeira ou orientação profissional.

## Critério de atualização

Este documento deve ser revisto sempre que uma limitação for resolvida, surgir uma nova dependência de dados ou o escopo funcional mudar.
