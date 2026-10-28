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

- [Roteiro simples para a apresentação acadêmica](./APRESENTACAO.md).
- [Decisões arquiteturais](./decisoes-arquiteturais/README.md)
- [Planos](./planos/README.md)
- [Procedimentos](./procedimentos/README.md)
- [Ambiente local em múltiplos computadores](./procedimentos/ambiente-multiplos-computadores.md)
- [Incidentes](./incidentes/README.md)
- [Segurança](./seguranca/README.md)
- [Interface](./interface/README.md)

## Convenções

- `A CONFIRMAR`: assunto que depende de validação do grupo ou da professora.
- `Conceitual`: descrição do comportamento esperado, sem compromisso com tecnologia.
- Identificadores como `RN-001` e `RI-001` permitem rastrear regras entre os documentos.

## Estado atual

Atualizado em 28/10/2026. No trabalho: **92 municípios, 1.332 CNAEs, 92 populações do Censo 2022 e uma coleta piloto CNPJ de Maricá/5611201 (620)**. Consulta demonstrativa com fontes/datas/avisos, desktop do piloto conferido pelo usuário. Verificador/importador em lote implementados, com hash/job UTC, transação e gravação explícita; simulação real aprovada (soma municipal 39.423), mas **carga do lote no MySQL real ainda pendente**. Suíte: 85 testes/447 asserções aprovados. Mapa e CRUD de perfis locais ao navegador; sem score aprovado ou H3 implementado. Consulte [STATUS.md](./STATUS.md), [plano](./planos/implementacao-inicial.md), [procedimento do lote](./procedimentos/comparacao-cnpj-municipios-rj.md) e [retomada na escola](./procedimentos/ambiente-multiplos-computadores.md#retomada-da-entrega-cnpj--28102026). O Git não transporta banco, JSON, dependências ou perfis do navegador.

O desenvolvimento ocorrerá em mais de um computador. Por decisão explícita do usuário, o `.env` desta aplicação está versionado e será recebido em outro clone; as dependências instaladas e o banco do XAMPP continuam locais. Antes de configurar ou atualizar um ambiente, siga o [procedimento para múltiplos computadores](./procedimentos/ambiente-multiplos-computadores.md), especialmente ao retornar ao computador da escola, onde podem existir arquivos ou migrations ainda não enviados ao GitHub.

As regras de negócio formam a primeira base para validação. As restrições de integridade e as classes são propostas iniciais derivadas dessas regras e devem ser revisadas depois que os pontos `A CONFIRMAR` forem decididos.
