# Plano de implementação inicial — RightPoint

Última atualização: 24/09/2026.

## Objetivo

Dar continuidade à base Laravel e construir, em etapas verificáveis, o fluxo de consulta de potencial comercial por atividade econômica e área geográfica. Este plano registra a proposta de sequência; não transforma decisões pendentes em requisitos aprovados.

## Estado confirmado

- Repositório local: `C:\xampp\htdocs\RightPoint`.
- Aplicação Laravel: `Rigth_Point/` (grafia atual da pasta, preservada).
- O usuário informou que criou o projeto Laravel para o RightPoint.
- `Rigth_Point/composer.json` declara PHP `^8.2` e Laravel `^12.0`.
- `Rigth_Point/routes/web.php` contém a rota inicial que retorna a view `welcome`.
- Os arquivos inspecionados de aplicação e banco contêm a estrutura inicial: model `User`, controller base, provider, factory, seeder e migrations de usuários, cache e jobs.
- A documentação contém minimundo, regras de negócio, restrições de integridade e modelo conceitual de classes.
- Não foram executados testes, servidor, migrations ou verificações de conexão com o banco nesta etapa documental. A instalação funcional do ambiente ainda precisa ser validada.

O model `User` fornecido pelo Laravel não significa que login ou cadastro foram aprovados como funcionalidades do RightPoint.

## Referências para retomada

1. [Minimundo](../contexto/minimundo.md).
2. [Regras de negócio](../contexto/regras-de-negocio.md).
3. [Restrições de integridade](../contexto/restricoes-de-integridade.md).
4. [Modelo inicial de classes](../arquitetura/modelo-de-classes.md).
5. [Limitações conhecidas](../contexto/limitacoes-conhecidas.md).

## Próxima etapa imediata

Revisar o modelo de dados e delimitar a primeira versão antes de criar migrations do domínio. Validar também o ambiente Laravel existente, sem presumir que a criação do projeto comprovou seu funcionamento.

### Decisões a confirmar

| Tema | Decisão necessária | Impacto |
| --- | --- | --- |
| Banco | Escolher o banco e confirmar a configuração local, sem divulgar credenciais. | Tipos, migrations e execução local. |
| Área geográfica | Definir se a primeira versão consulta município, bairro ou coordenadas com raio. | Relacionamentos, entrada da consulta e seleção de concorrentes. |
| Atividades | Definir uso de CNAE, atividades principal/secundárias e critério de equivalência. | Catálogo e associação com estabelecimentos. |
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
- [ ] Verificar versões instaladas, dependências e execução básica do Laravel.
- [ ] Verificar a configuração do banco sem expor secrets e consultar o estado das migrations.
- [ ] Registrar as decisões aprovadas na documentação pertinente.

Critério de conclusão: escopo inicial explícito e ambiente mínimo validado, com limitações registradas.

### 2. Implementar a base de dados do domínio

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
