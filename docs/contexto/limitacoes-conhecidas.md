# Limitações Conhecidas - RightPoint

## Estado da modelagem

Atualizado em 04/11/2026. O RightPoint possui mapa/consulta demonstrativa, catálogos, 92 populações do Censo 2022 e 368 pares município/CNAE em quatro CNAEs, com 461 coletas históricas neste banco. Carga conferida contra o JSON; exemplos dos novos recortes conferidos visualmente por relato do usuário. Indicador por 10 mil habitantes implementado e validado em 04/11, com interpretação exploratória e referências visíveis. Não há fórmula validada de score. Banco e perfis do navegador são locais; ver [matriz](../arquitetura/matriz-dados-diagnostico.md), [status](../STATUS.md) e [plano](../planos/implementacao-inicial.md).

## Limitações funcionais

| Limitação | Impacto | Tratamento atual |
| --- | --- | --- |
| Score experimental 50/50, sem validação comercial | Posições relativas não demonstram sucesso; os pesos e a hipótese podem mudar a ordem entre cidades. | Método versionado e fatores explicados na [decisão 001](../decisoes-arquiteturais/001-score-piloto-rj.md); revisar sensibilidade e critérios com grupo/professora. |
| Renda nominal de 2022 | 92 rendas carregadas e recorte conferido; renda entra no experimento, mas não mede renda atual, salário individual ou gasto com a atividade. | Rotular referência/amostra, sem substituir por PNAD estadual ou atualizar por inflação automaticamente. Perfil etário/clima continuam a investigar. |
| Amostra OpenCNPJ de apenas 10 registros | Validação local de campos/filtros/duplicatas não comprova cobertura nem total de concorrentes. | Manter fora do diagnóstico; definir aquisição completa, procedência e vínculo Receita↔IBGE antes da importação. |
| Contagem restrita a quatro CNAEs principais nos 92 municípios do RJ | Não cobre outras atividades, informais, CNAEs secundários/equivalentes, funcionamento real ou cobertura comercial independente. | Ler coleta mais recente da mesma fonte/critério, com avisos. Ausência → NULL; zero não comprova ausência real. Padarias separadas e 9602501 amplo. |
| Dados e cargas restritos ao banco de cada máquina | 368 novos pares carregados e conferidos na origem; as 461 coletas históricas não acompanham o Git. | Conferir ambiente/dados e transportar JSON separadamente. Escrita real do usuário conferida por leitura; concorrência e reimportação real do novo comando não exercitadas pelo agente. |
| Período/versão CNPJ desconhecidos e referência distinta da população/renda 2022 | 2026 é premissa autorizada pelo usuário, não confirmação da fonte. O índice não descreve integralmente 2026. | Preservar NULL; declarar premissa para a fotografia fixada, método experimental e anos de cada fator. Novas coletas não herdam a premissa. |
| Validação interativa ainda parcial | Capturas anteriores e relato dos novos recortes aprovam exemplos, sem comprovar todos os municípios, mobile, mapa e CRUD. | Executar verificações proporcionais às próximas alterações; não repetir exemplos já conferidos como bloqueio. |
| Verificador limitado ao exercício de Maricá e CNPJ numérico | Filtros estão fixos em RJ/Receita `5853`/CNAE `5611201`/`Ativa`; não valida dígitos verificadores nem aceita CNPJ alfanumérico. | Não tratar como importador genérico; ampliar contrato e testes em etapa específica. Testes adicionais de erro foram adiados pelo usuário em 27/10/2026. |
| Equivalência entre atividades indefinida | A contagem de concorrentes pode variar conforme o critério. | Registrar como regra pendente. |
| População importada apenas no banco local do notebook do trabalho | Outro computador pode mostrar o indicador ausente apesar de ter recebido o código pelo Git. | Conferir migrations e dados locais; repetir a carga validada quando necessário. Não confundir ausência de dados com população zero. |
| Critério inicial de concorrência ainda não validado academicamente | CNAE principal exato e situação ativa são propostas que podem subestimar concorrência. | Revisar com grupo/professora; não contar inativos como ativos nem ausência de dados como zero. |
| Agenda e validade dos dados ainda indefinidas | Uma versão local pode ficar defasada após falha de atualização. | Planejar uma fonte por vez; frequência, retentativas e prazo de validade `A CONFIRMAR`. |
| Qualidade dos dados externos | Dados públicos podem estar ausentes, desatualizados ou sem coordenadas. | Informar insuficiência de dados na análise. |
| Histórico ainda opcional | A persistência necessária pode mudar. | Modelo prevê rastreabilidade sem torná-la decisão final. |
| Perfis do negócio apenas no navegador, sem login | O CRUD protótipo usa `localStorage`; perfis não acompanham o Git nem sincronizam entre computadores. | Cadastro, propriedade dos perfis, persistência e campos adicionais `A CONFIRMAR` antes de um CRUD compartilhado. |

## Limitações do resultado

- Dados de anos diferentes limitam a finalidade: piloto usa população/renda 2022 e CNPJ 2026 assumido explicitamente. Seleção uniforme e elegibilidade técnica implementadas; a premissa não confirma competência/cobertura e não valida utilidade comercial. Ver [política temporal](../planos/compatibilidade-temporal-diagnostico.md). População estimada 2026 e histórico de análises continuam pendentes; hash sozinho não recupera uma análise antiga.

- o score é uma estimativa baseada nos dados disponíveis, não uma probabilidade estatística;
- fatores como qualidade do produto, gestão, preço, imóvel e fluxo local podem não estar representados;
- correlação entre indicadores e sucesso comercial não significa causalidade;
- o resultado não substitui pesquisa de mercado, análise financeira ou orientação profissional.

## Critério de atualização

Este documento deve ser revisto sempre que uma limitação for resolvida, surgir uma nova dependência de dados ou o escopo funcional mudar.
