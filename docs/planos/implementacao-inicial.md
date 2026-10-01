# Plano de implementação inicial — RightPoint

Última atualização: 01/10/2026.

## Objetivo

Dar continuidade à base Laravel e construir, em etapas verificáveis, o fluxo de consulta de potencial comercial por atividade econômica e área geográfica. Este plano registra a proposta de sequência; não transforma decisões pendentes em requisitos aprovados.

## Estado confirmado

- Repositório local: `C:\xampp\htdocs\RightPoint`.
- Aplicação Laravel: `Right_Point/` (renomeação intencional confirmada pelo usuário em 01/10/2026).
- O usuário informou que criou o projeto Laravel para o RightPoint.
- `Right_Point/composer.json` declara PHP `^8.2` e Laravel `^12.0`.
- `Right_Point/routes/web.php` contém a rota inicial que retorna a view `welcome`.
- A aplicação contém a estrutura inicial do Laravel e os models `Municipio` e `AtividadeEconomica`, com migrations próprias para os dois catálogos.
- A documentação contém minimundo, regras de negócio, restrições de integridade e modelo conceitual de classes.
- Neste computador, o Laravel e a conexão com MariaDB foram verificados, as três migrations padrão e as duas migrations de catálogo foram aplicadas. Os dois testes iniciais passaram após as migrations de catálogo. O frontend ainda não foi validado porque a instalação npm está bloqueada por um erro de certificado. Consulte os detalhes no [procedimento de ambiente local](../procedimentos/ambiente-multiplos-computadores.md).
- O usuário confirmou que pretende utilizar o serviço MySQL compatível do XAMPP, administrado pelo phpMyAdmin. Este computador usa MariaDB 10.4.32 e banco local `rightpoint`; o nome obrigatório para o projeto e a configuração de outros computadores permanecem `A CONFIRMAR`.
- O `.env` foi incluído no commit por decisão explícita do usuário. O banco local, `vendor/` e `node_modules/` não são sincronizados pelo Git. A continuidade entre computadores deve seguir o [procedimento de ambiente local](../procedimentos/ambiente-multiplos-computadores.md).
- As migrations de municípios e atividades econômicas foram criadas neste computador. No computador da escola, ainda é necessário verificar se existem migrations ou outros arquivos locais não enviados antes de qualquer sincronização.

O model `User` fornecido pelo Laravel não significa que login ou cadastro foram aprovados como funcionalidades do RightPoint.

## Referências para retomada

1. [Minimundo](../contexto/minimundo.md).
2. [Regras de negócio](../contexto/regras-de-negocio.md).
3. [Restrições de integridade](../contexto/restricoes-de-integridade.md).
4. [Modelo inicial de classes](../arquitetura/modelo-de-classes.md).
5. [Limitações conhecidas](../contexto/limitacoes-conhecidas.md).

## Próxima etapa imediata

As migrations iniciais de municípios e atividades econômicas por CNAE já foram criadas e aplicadas neste computador. A próxima etapa é validar os models e definir os dados de demonstração e as próximas estruturas do domínio; instalação npm e conferência dos demais computadores continuam pendentes.

### Decisões a confirmar

| Tema | Decisão necessária | Impacto |
| --- | --- | --- |
| Banco | MySQL compatível fornecido pelo XAMPP foi indicado; confirmar nome do banco, versão MySQL/MariaDB, collation e configuração local sem divulgar credenciais. | Tipos, migrations e execução local. |
| Área geográfica | Município foi escolhido para a primeira versão; bairros e coordenadas com raio continuam fora do recorte inicial. | Relacionamentos, entrada da consulta e seleção de concorrentes. |
| Atividades | CNAE foi escolhido para identificar as atividades; cada registro inicial possui um código obrigatório e único. Atividades principal/secundárias dos estabelecimentos e equivalência continuam `A CONFIRMAR`. | Catálogo e associação com estabelecimentos. |
| Concorrentes | Definir quais situações cadastrais serão consideradas. | Resultado da consulta. |
| Histórico | Definir se análises serão persistidas na primeira versão. | Armazenamento de resultados e dados usados no cálculo. |
| Identidade | Definir necessidade de login e de análises vinculadas ao usuário. | Autenticação e autorização. |
| Interface | Escolher a abordagem de frontend compatível com Laravel. | Implementação das telas e ferramentas necessárias. |
| Dados externos | Escolher fontes e verificar cobertura, acesso e atualização. | Viabilidade dos indicadores e da concorrência. |
| Score | Validar fórmula, pesos, dados mínimos e tratamento de ausência de dados. | Cálculo reproduzível e justificativa. |

Resolver cada pendência antes da etapa que depende dela. Fontes e fórmula podem permanecer pendentes durante um protótipo de consulta, desde que ele não apresente um score como validado.

## Sequência proposta

### 1. Validar escopo e ambiente

- [ ] Confirmar as decisões que afetam o primeiro modelo de dados.
- [ ] Conferir e preservar o ambiente existente no computador da escola, incluindo migrations ainda não enviadas.
- [ ] Verificar versões instaladas, dependências e execução básica do Laravel.
- [ ] Verificar a configuração do banco sem expor secrets e consultar o estado das migrations.
- [ ] Registrar as decisões aprovadas na documentação pertinente.

Critério de conclusão: escopo inicial explícito e ambiente mínimo validado, com limitações registradas.

### 2. Implementar a base de dados do domínio

- [x] Criar e aplicar neste computador as migrations iniciais de municípios e atividades econômicas por CNAE.
- [ ] Revisar atributos, cardinalidades e restrições do modelo conceitual.
- [ ] Definir migrations e models para atividades econômicas, regiões, indicadores, fontes e estabelecimentos conforme o recorte aprovado.
- [ ] Incluir relacionamentos necessários, sem converter automaticamente cada classe conceitual em tabela.
- [ ] Criar dados de demonstração identificados como fictícios, caso essa abordagem seja adotada.
- [ ] Validar integridade e relacionamentos em banco de desenvolvimento ou teste.

Critério de conclusão: estrutura reproduzível por migrations, coerente com as regras aprovadas. Avaliar dados existentes e reversibilidade antes de executar mudanças estruturais; não usar reset destrutivo como rotina.

### 3. Construir a primeira consulta

- [ ] Criar formulário para atividade e área geográfica escolhida.
- [ ] Validar entradas e consultar indicadores e concorrentes conforme os critérios aprovados.
- [ ] Apresentar resultados e estados de erro, ausência ou insuficiência de dados.
- [ ] Identificar claramente dados demonstrativos, caso utilizados.
- [ ] Validar o fluxo no navegador e os testes relevantes de entrada e seleção de dados.

Critério de conclusão: formulário → consulta → resultado funcionando. Enquanto não houver fórmula aprovada, informar que o score está indisponível.

### 4. Integrar fontes reais e implementar o score

- [ ] Validar fontes, cobertura geográfica e procedência dos dados.
- [ ] Implementar integrações com tratamento de falhas e dados ausentes.
- [ ] Implementar fórmula e pesos aprovados, com justificativa baseada nos fatores efetivamente usados.
- [ ] Testar cálculos, limites e insuficiência de dados.
- [ ] Implementar histórico e preservação do contexto da análise, se aprovados.

Critério de conclusão: resultado rastreável, cálculo reproduzível e limitações apresentadas ao usuário.

## Cuidados de continuidade

- Preservar mudanças locais e consultar instruções do projeto antes de editar código.
- Não assumir MySQL apenas porque o repositório está dentro do XAMPP.
- Não registrar valores do `.env`, credenciais ou tokens na documentação.
- Não introduzir IA, RAG ou serviços externos sem necessidade definida pelo escopo.
- Atualizar este plano ao concluir etapas, registrando verificações realmente executadas e pendências remanescentes.

## Ponto de retomada

Na próxima sessão, começar pela etapa 1: conferir o estado do repositório e do Laravel, revisar as referências de domínio e resolver as decisões necessárias para a primeira modelagem física. Nenhuma migration de domínio ou funcionalidade foi implementada nesta atualização documental.
