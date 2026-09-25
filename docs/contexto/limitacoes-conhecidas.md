# Limitações Conhecidas - RightPoint

## Estado da modelagem

O RightPoint está em fase de definição do domínio, com a base Laravel criada em `Rigth_Point/`. Ainda não existem funcionalidades do domínio implementadas nos arquivos inspecionados, integração com fontes públicas ou fórmula validada para o score. A configuração e a conectividade do banco não foram verificadas; as migrations existentes são as iniciais do Laravel. Consulte o [plano de continuidade](../planos/implementacao-inicial.md).

## Limitações funcionais

| Limitação | Impacto | Tratamento atual |
| --- | --- | --- |
| Fórmula e pesos do score indefinidos | Ainda não é possível reproduzir um cálculo real. | `A CONFIRMAR` com o grupo. |
| Fontes oficiais não selecionadas | Disponibilidade, atualização e formato dos dados são desconhecidos. | Manter a arquitetura independente de uma fonte específica. |
| Equivalência entre atividades indefinida | A contagem de concorrentes pode variar conforme o critério. | Registrar como regra pendente. |
| Escopo geográfico indefinido | Não está decidido se a primeira versão cobrirá bairro, município, coordenadas ou todos. | Validar antes da modelagem física. |
| Tratamento de empresas inativas indefinido | A concorrência pode ser superestimada. | Não presumir que todo CNPJ encontrado é concorrente ativo. |
| Qualidade dos dados externos | Dados públicos podem estar ausentes, desatualizados ou sem coordenadas. | Informar insuficiência de dados na análise. |
| Histórico ainda opcional | A persistência necessária pode mudar. | Modelo prevê rastreabilidade sem torná-la decisão final. |
| Cadastro de usuário fora do escopo atual | Não há definição de identidade, autenticação ou propriedade das análises. | Manter o empreendedor como ator, sem classe obrigatória nesta etapa. |

## Limitações do resultado

- o score é uma estimativa baseada nos dados disponíveis;
- fatores como qualidade do produto, gestão, preço, imóvel e fluxo local podem não estar representados;
- correlação entre indicadores e sucesso comercial não significa causalidade;
- o resultado não substitui pesquisa de mercado, análise financeira ou orientação profissional.

## Critério de atualização

Este documento deve ser revisto sempre que uma limitação for resolvida, surgir uma nova dependência de dados ou o escopo funcional mudar.
