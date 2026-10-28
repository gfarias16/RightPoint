# Limitações Conhecidas - RightPoint

## Estado da modelagem

O RightPoint possui mapa e consulta demonstrativa, catálogos de municípios/CNAEs, população do Censo 2022 e contagem piloto de Maricá/CNAE 5611201 integrada à consulta (620 na base). Recorte inicial: 92 municípios do RJ, mas a carga CNPJ não cobre todos. Não há fórmula validada para o score. O banco de cada máquina é independente; consulte a [matriz](../arquitetura/matriz-dados-diagnostico.md), o [status](../STATUS.md) e o [plano](../planos/implementacao-inicial.md). Atualizado em 28/10/2026.

## Limitações funcionais

| Limitação | Impacto | Tratamento atual |
| --- | --- | --- |
| Fórmula e pesos do score indefinidos | Ainda não é possível reproduzir um cálculo real. | `A CONFIRMAR` com o grupo. |
| Demais fontes candidatas ainda não integradas | Disponibilidade, cobertura, atualização, licença e formato de CNPJ, ANP, INMET e investimentos públicos ainda precisam de estudo. | Conferir cada fonte em [FONTES_DADOS](../FONTES_DADOS.md) antes de incorporá-la ao diagnóstico. |
| Amostra OpenCNPJ de apenas 10 registros | Validação local de campos/filtros/duplicatas não comprova cobertura nem total de concorrentes. | Manter fora do diagnóstico; definir aquisição completa, procedência e vínculo Receita↔IBGE antes da importação. |
| Contagem CNPJ restrita ao piloto Maricá/5611201 | O agregado 620 não cobre outros municípios/atividades, informais, CNAEs secundários/equivalentes ou funcionamento real. | Ler a coleta mais recente da mesma fonte/critério e exibir recorte/avisos. Sem coleta → NULL/indisponível; zero registrado não garante ausência real. |
| Lote RJ validado, importador testado, carga MySQL real pendente | Os 92 grupos/soma municipal 39.423 ainda não estão publicados no diagnóstico dos demais municípios. | Verificador e importador simulam; usar `--gravar` só ao decidir carregar o banco local. Transação/hash/UTC/idempotência testados em memória; concorrência MySQL não exercitada. Preservar piloto. |
| Período/versão CNPJ desconhecidos e referência distinta da população 2022 | Data de consulta não comprova competência nem comparabilidade temporal. | Preservar NULL e avisar; não calcular score ou taxa automaticamente. |
| Conferência visual da contagem pendente | Código e HTTP validados não comprovam apresentação/responsividade. | A ferramenta negou acesso ao navegador em localhost; conferir desktop/mobile manualmente, sem contornar a permissão. |
| Verificador limitado ao exercício de Maricá e CNPJ numérico | Filtros estão fixos em RJ/Receita `5853`/CNAE `5611201`/`Ativa`; não valida dígitos verificadores nem aceita CNPJ alfanumérico. | Não tratar como importador genérico; ampliar contrato e testes em etapa específica. Testes adicionais de erro foram adiados pelo usuário em 27/10/2026. |
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
