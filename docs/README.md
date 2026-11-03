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
- Decisões arquiteturais: diretório previsto, ainda não criado neste checkout.
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

Atualizado em 03/11/2026. Banco local: 92 municípios, 1.332 CNAEs, 92 populações e 93 coletas de restaurantes (lote de 92 municípios/soma 39.423 e piloto preservado). Os exemplos com coleta e sem coleta foram conferidos por capturas/relato. Novo comando `cnpj:importar-contagens-piloto` preparado para os quatro CNAEs dos três perfis: valida grupos observados, mantém ausências indisponíveis e só grava fotografias conferidas por hash/UTC. Suíte completa: **126 testes/701 asserções aprovados**; simulações reais preservaram banco/JSON. O usuário já executou a query e exportou o novo JSON: simulação real aprovou 368 pares, 92 municípios por CNAE e somas 39.423/25.188/8.992/102.086, preservando banco e arquivo. **Próximo passo:** conferir início com fuso/UTC e SQL/completude do job existente, registrar hash/metadados e só depois decidir a carga; gravação ainda bloqueada. Não repetir BigQuery. Sem nova migration ou mudança visual. [Procedimento](./procedimentos/contagens-perfis-piloto.md), [STATUS](./STATUS.md) e [critérios propostos](./planos/criterios-diagnostico-piloto.md). Renda, indicadores derivados, união de padarias e score ainda pendentes. Git não transporta banco, JSON, dependências ou perfis do navegador.

O enunciado original disponível trata da modelagem do 1º bimestre, não define a entrega posterior de implementação. Minimundo, regras, restrições e classes são a base acadêmica; planos e protótipo devem ser distinguidos de requisitos posteriores ainda a confirmar com o usuário.

O desenvolvimento ocorrerá em mais de um computador. Por decisão explícita do usuário, o `.env` desta aplicação está versionado e será recebido em outro clone; as dependências instaladas e o banco do XAMPP continuam locais. Antes de configurar ou atualizar um ambiente, siga o [procedimento para múltiplos computadores](./procedimentos/ambiente-multiplos-computadores.md), especialmente ao retornar ao computador da escola, onde podem existir arquivos ou migrations ainda não enviados ao GitHub.

As regras de negócio formam a primeira base para validação. As restrições de integridade e as classes são propostas iniciais derivadas dessas regras e devem ser revisadas depois que os pontos `A CONFIRMAR` forem decididos.
