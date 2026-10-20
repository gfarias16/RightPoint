# Limitações Conhecidas - RightPoint

## Estado da modelagem

O RightPoint está em fase de definição do domínio, com a aplicação Laravel em `Right_Point/`. O recorte inicial são os 92 municípios do RJ. Já existem mapa e consulta demonstrativa, catálogo de municípios/CNAEs e importação local da população do Censo 2022 no notebook do trabalho. Não há integração de estabelecimentos/CNPJ nem fórmula validada para o score. O banco de cada máquina é independente; consulte a [matriz de dados e o fluxograma](../arquitetura/matriz-dados-diagnostico.md), o [status](../STATUS.md) e o [plano de continuidade](../planos/implementacao-inicial.md).

## Limitações funcionais

| Limitação | Impacto | Tratamento atual |
| --- | --- | --- |
| Fórmula e pesos do score indefinidos | Ainda não é possível reproduzir um cálculo real. | `A CONFIRMAR` com o grupo. |
| Demais fontes candidatas ainda não integradas | Disponibilidade, cobertura, atualização, licença e formato de CNPJ, ANP, INMET e investimentos públicos ainda precisam de estudo. | Conferir cada fonte em [FONTES_DADOS](../FONTES_DADOS.md) antes de incorporá-la ao diagnóstico. |
| Equivalência entre atividades indefinida | A contagem de concorrentes pode variar conforme o critério. | Registrar como regra pendente. |
| População importada apenas no banco local do notebook do trabalho | Outro computador pode mostrar o indicador ausente apesar de ter recebido o código pelo Git. | Conferir migrations e dados locais; repetir a carga validada quando necessário. Não confundir ausência de dados com população zero. |
| Critério inicial de concorrência ainda não validado academicamente | CNAE principal exato e situação ativa são propostas que podem subestimar concorrência. | Revisar com grupo/professora; não contar inativos como ativos nem ausência de dados como zero. |
| Agenda e validade dos dados ainda indefinidas | Uma versão local pode ficar defasada após falha de atualização. | Planejar uma fonte por vez; frequência, retentativas e prazo de validade `A CONFIRMAR`. |
| Qualidade dos dados externos | Dados públicos podem estar ausentes, desatualizados ou sem coordenadas. | Informar insuficiência de dados na análise. |
| Histórico ainda opcional | A persistência necessária pode mudar. | Modelo prevê rastreabilidade sem torná-la decisão final. |
| Perfis do negócio apenas no navegador, sem login | O CRUD protótipo usa `localStorage`; perfis não acompanham o Git nem sincronizam entre computadores. | Cadastro, propriedade dos perfis, persistência e campos adicionais `A CONFIRMAR` antes de um CRUD compartilhado. |

## Limitações do resultado

- o score é uma estimativa baseada nos dados disponíveis, não uma probabilidade estatística;
- fatores como qualidade do produto, gestão, preço, imóvel e fluxo local podem não estar representados;
- correlação entre indicadores e sucesso comercial não significa causalidade;
- o resultado não substitui pesquisa de mercado, análise financeira ou orientação profissional.

## Critério de atualização

Este documento deve ser revisto sempre que uma limitação for resolvida, surgir uma nova dependência de dados ou o escopo funcional mudar.
