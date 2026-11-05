# Documentação do RightPoint

Esta pasta reúne a documentação V2 do RightPoint. O projeto possui uma modelagem acadêmica inicial e uma base Laravel em `Right_Point/`. As tabelas iniciais de municípios e atividades econômicas foram criadas; há um fluxo visual demonstrativo, mas a análise completa ainda está pendente. A arquitetura descrita permanece conceitual, salvo as escolhas explicitamente confirmadas.

## Fontes originais

- [Minimundo original](./RightPoint%20-%20MINIMUNDO.docx)
- [Enunciado do trabalho](./Trabalho%20do%201%20Bimestre%20-%20ProfLucieneMotta_2026.2.pdf)

Os arquivos originais são referências da atividade. Os arquivos Markdown são a versão de trabalho, preparada para revisão, histórico no Git e evolução pelo grupo.

## Navegação

### Contexto do domínio

- [Minimundo estruturado](./contexto/minimundo.md)
- [Regras de negócio](./contexto/regras-de-negocio.md)
- [Restrições de integridade](./contexto/restricoes-de-integridade.md)
- [Glossário](./contexto/glossario.md)
- [Limitações conhecidas](./contexto/limitacoes-conhecidas.md)
- [Fontes de dados candidatas e primeiros testes do IBGE](./FONTES_DADOS.md)

### Arquitetura conceitual

- [Visão geral](./arquitetura/visao-geral.md)
- [Mapa de serviços](./arquitetura/mapa-de-servicos.md)
- [Fluxo de dados](./arquitetura/fluxo-de-dados.md)
- [Matriz de dados e fluxograma do diagnóstico](./arquitetura/matriz-dados-diagnostico.md)
- [Modelo inicial de classes](./arquitetura/modelo-de-classes.md)

### Evolução e governança

- [Referências temporais do diagnóstico e score](./planos/compatibilidade-temporal-diagnostico.md): política v0.2, com cenário experimental autorizado e evolução pendente.
- [Roteiro simples para a apresentação acadêmica](./APRESENTACAO.md).
- [Decisão 001 — score piloto](./decisoes-arquiteturais/001-score-piloto-rj.md): método implementado, fórmula explicada, premissa CNPJ 2026, exemplos e limites para estudo/apresentação.
- [Sensibilidade dos pesos](./procedimentos/sensibilidade-score.md): comando de leitura, definição das posições e interpretação; [relatório completo de 04/11](./analises/sensibilidade-score-2026-11-04.md) para os 92 municípios nos quatro CNAEs.
- [Planos](./planos/README.md)
- [Procedimentos](./procedimentos/README.md)
- [Ambiente local em múltiplos computadores](./procedimentos/ambiente-multiplos-computadores.md)
- [Incidentes](./incidentes/README.md)
- Segurança: diretório documental previsto, ainda não criado neste checkout; orientações atuais em [AGENTS.md](../AGENTS.md).
- [Interface](./interface/README.md)

## Convenções

- `A CONFIRMAR`: assunto que depende de validação do grupo ou da professora.
- `Conceitual`: descrição do comportamento esperado, sem compromisso com tecnologia.
- Identificadores como `RN-001` e `RI-001` permitem rastrear regras entre os documentos.

## Estado atual

**Correções da revisão — 05/11:** indicador por habitante agora usa somente população IBGE/Censo 2022, em pessoas e no recorte identificado; outras fontes/unidades não viram denominador. Teste inicial independente do banco local. Suíte completa isolada: **227 testes/1.208 asserções aprovados**. Sem mudança no MySQL ou nos pesos do score. Retomada e validações em [STATUS](./STATUS.md).

**Refinamento e análise dos pesos — 04/11:** diagnóstico compacto com nota/quatro indicadores/fatores e detalhes expansíveis, preservando transparência das fontes. Sensibilidade completa implementada nos 92 municípios/quatro CNAEs, sem alterar pesos 50/50 da consulta. [Relatório real](./analises/sensibilidade-score-2026-11-04.md) e [procedimento](./procedimentos/sensibilidade-score.md). Suíte 220 testes/1.154 asserções; comando real preservou indicadores/coletas. Revisão metodológica, população estimada e histórico continuam próximos trabalhos.

**Atualização mais recente — 04/11/2026:** leitura local confirmou 92 rendas, 92 populações (184 indicadores) e 461 coletas CNPJ. Score experimental 50/50 implementado para os quatro CNAEs, comparando os 92 municípios dentro do mesmo CNAE. População/renda 2022; CNPJ 2026 assumido por decisão do usuário, com metadados originais NULL preservados. Maricá/restaurantes 50,0 e Barra Mansa 59,3 conferidos no navegador com dados reais. Consulta local sem escrita/rede; falta de fator no recorte bloqueia a nota. Método/estudo na [decisão 001](./decisoes-arquiteturais/001-score-piloto-rj.md). Histórico, população estimada e validação comercial continuam pendentes. Os parágrafos seguintes registram as etapas anteriores e foram superados por esta atualização.

Atualizado em 04/11/2026. Banco local: 92 municípios, 1.332 CNAEs, 92 populações e **461 coletas históricas**. Usuário carregou os quatro CNAEs; leitura conferiu os 368 novos pares do instante `2026-11-03 20:41:41` UTC contra o JSON, sem divergências, e preservação das 93 coletas anteriores. Cada CNAE tem 92 municípios, somas 39.423/25.188/8.992/102.086. Job/SQL conferidos por captura; período/versão NULL. Oito consultas diretas ao controller em Barra Mansa/Maricá retornaram a nova coleta e score NULL; não são testes visuais/HTTP externo. Importador testado anteriormente: 126 testes/701 asserções. **Próximo passo:** conferir os novos CNAEs na interface e depois implementar razão descritiva por habitante. Renda, união de padarias e score pendentes. Ver [procedimento](./procedimentos/contagens-perfis-piloto.md), [STATUS](./STATUS.md) e [critérios](./planos/criterios-diagnostico-piloto.md). Git não transporta banco, JSON, dependências ou perfis do navegador; não repetir BigQuery ou importações como rotina.

**Indicador entregue em 04/11:** razão por 10 mil habitantes no backend/API/tela, com estados/motivos e referências exploratórias. Navegador real confirmou 31,43 em Maricá e 18,13 em Barra Mansa; ausência de coleta preservada. Suíte final 147 testes/804 asserções. URLs corrigidas para execução em subpasta do Apache e assets com versão para evitar cache antigo. Próximo passo atual: renda municipal já investigada; importador/API/tela preparados e simulação aprovada, conforme atualização abaixo. Esta atualização substitui a pendência anterior do indicador.

**Renda preparada em 04/11:** Censo 2022/amostra, tabela 10295, variável 13431, média nominal domiciliar mensal por pessoa. API oficial retornou os 92 municípios. Novo comando `ibge:importar-renda-rj` simula; `--gravar` é explícito e transacional. Suíte final 179 testes/954 asserções. Banco permanece com 92 populações, zero rendas e 461 coletas CNPJ; tela positiva testada com resposta simulada. **Próximo passo:** carga local, depois conferência dos 92 valores e interface. [Procedimento](./procedimentos/renda-municipal-rj.md). Sem renda no score.

O enunciado original disponível trata da modelagem do 1º bimestre, não define a entrega posterior de implementação. Minimundo, regras, restrições e classes são a base acadêmica; planos e protótipo devem ser distinguidos de requisitos posteriores ainda a confirmar com o usuário.

O desenvolvimento ocorrerá em mais de um computador. Por decisão explícita do usuário, o `.env` desta aplicação está versionado e será recebido em outro clone; as dependências instaladas e o banco do XAMPP continuam locais. Antes de configurar ou atualizar um ambiente, siga o [procedimento para múltiplos computadores](./procedimentos/ambiente-multiplos-computadores.md), especialmente ao retornar ao computador da escola, onde podem existir arquivos ou migrations ainda não enviados ao GitHub.

As regras de negócio formam a primeira base para validação. As restrições de integridade e as classes são propostas iniciais derivadas dessas regras e devem ser revisadas depois que os pontos `A CONFIRMAR` forem decididos.
