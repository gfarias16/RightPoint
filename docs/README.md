# Documentação do RightPoint

Esta pasta reúne a documentação V2 do RightPoint. O projeto possui uma modelagem acadêmica inicial e uma base Laravel em `Right_Point/`. As tabelas iniciais de municípios e atividades econômicas foram criadas; o fluxo de consulta ainda está pendente. A arquitetura descrita permanece conceitual, salvo as escolhas explicitamente confirmadas.

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

### Arquitetura conceitual

- [Visão geral](./arquitetura/visao-geral.md)
- [Mapa de serviços](./arquitetura/mapa-de-servicos.md)
- [Fluxo de dados](./arquitetura/fluxo-de-dados.md)
- [Modelo inicial de classes](./arquitetura/modelo-de-classes.md)

### Evolução e governança

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

Atualizado em 01/10/2026. A pasta Laravel foi renomeada intencionalmente para `Right_Point/`, e as migrations iniciais do domínio foram executadas neste computador. O fluxo de consulta do RightPoint ainda não foi implementado. Para retomar o desenvolvimento, consulte o [plano de continuidade](./planos/implementacao-inicial.md), que registra o estado verificado, as decisões pendentes e a próxima etapa.

O desenvolvimento ocorrerá em mais de um computador. Por decisão explícita do usuário, o `.env` desta aplicação está versionado e será recebido em outro clone; as dependências instaladas e o banco do XAMPP continuam locais. Antes de configurar ou atualizar um ambiente, siga o [procedimento para múltiplos computadores](./procedimentos/ambiente-multiplos-computadores.md), especialmente ao retornar ao computador da escola, onde podem existir arquivos ou migrations ainda não enviados ao GitHub.

As regras de negócio formam a primeira base para validação. As restrições de integridade e as classes são propostas iniciais derivadas dessas regras e devem ser revisadas depois que os pontos `A CONFIRMAR` forem decididos.
