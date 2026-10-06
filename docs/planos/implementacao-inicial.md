# Plano de implementação inicial — RightPoint

Última atualização: 06/10/2026 (matriz e fluxo documentais; sem implementação nova).

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
- Um registro de 01/10/2026 informa cinco migrations aplicadas e dois testes iniciais aprovados, mas não identifica com segurança a máquina. No notebook do trabalho, as mesmas cinco migrations foram aplicadas ao banco inicialmente vazio em 05/10/2026 e os dois testes iniciais também passaram. O frontend ainda não foi validado neste notebook. Consulte o [procedimento de ambiente local](../procedimentos/ambiente-multiplos-computadores.md).
- O banco de desenvolvimento padronizado é `rightpoint` (minúsculas), servido pelo MySQL/MariaDB do XAMPP. Neste notebook, a conexão verificada usa `127.0.0.1:3306`, usuário `root`, senha vazia e MariaDB 10.4.32; confira se a escola está configurada de modo equivalente antes de executar migrations lá.
- O `.env` foi incluído no commit por decisão explícita do usuário. O banco local, `vendor/` e `node_modules/` não são sincronizados pelo Git. A continuidade entre computadores deve seguir o [procedimento de ambiente local](../procedimentos/ambiente-multiplos-computadores.md).
- As migrations de municípios e atividades econômicas estão versionadas. No computador da escola, ainda é necessário verificar se existem migrations ou outros arquivos locais não enviados antes de qualquer sincronização.

O model `User` fornecido pelo Laravel não significa que login ou cadastro foram aprovados como funcionalidades do RightPoint.

## Referências para retomada

1. [Minimundo](../contexto/minimundo.md).
2. [Regras de negócio](../contexto/regras-de-negocio.md).
3. [Restrições de integridade](../contexto/restricoes-de-integridade.md).
4. [Modelo inicial de classes](../arquitetura/modelo-de-classes.md).
5. [Limitações conhecidas](../contexto/limitacoes-conhecidas.md).
6. [Matriz de dados e fluxograma do diagnóstico](../arquitetura/matriz-dados-diagnostico.md).

## Próxima etapa imediata

As migrations iniciais de municípios e atividades econômicas por CNAE estão versionadas, foram aplicadas no notebook do trabalho e aparecem como `Ran` na escola em 05/10/2026. Os dois models foram validados na escola. Um comando experimental consultou município e população no IBGE em modo leitura. A tabela `fontes_dados` foi criada e aplicada no banco local da escola para permitir rastrear a origem dos dados futuros; nenhuma fonte candidata foi importada. A [matriz e o fluxograma](../arquitetura/matriz-dados-diagnostico.md) agora documentam o recorte inicial dos 92 municípios do RJ e propostas de critério, sem validar a fórmula nem alterar código ou banco. A instalação npm e as próximas estruturas do domínio continuam pendentes.

### Decisões a confirmar

| Tema | Decisão necessária | Impacto |
| --- | --- | --- |
| Banco | Nome `rightpoint` definido; confirmar na escola a versão do MySQL/MariaDB, collation e conexão local antes de aplicar novas migrations. | Compatibilidade e execução em cada computador. |
| Área geográfica | Recorte inicial definido: 92 municípios do RJ, selecionados por busca ou mapa via código IBGE; bairros e raio ficam para depois. Verificar a edição da malha e a cobertura dos dados. | Relacionamentos, mapa e seleção de concorrentes. |
| Atividades | CNAE identifica a atividade; a primeira contagem por CNAE **principal exato** é proposta para validar com o grupo. CNAEs secundários/equivalentes continuam `A CONFIRMAR`. | Catálogo e associação com estabelecimentos. |
| Concorrentes | Proposta inicial: somente CNPJs ativos, no mesmo município e com CNAE principal exato. Validar regra e correspondência entre códigos municipais da Receita e do IBGE. | Resultado e confiabilidade da contagem, inclusive zero. |
| Histórico | Definir se análises serão persistidas na primeira versão. | Armazenamento de resultados e dados usados no cálculo. |
| Identidade e perfil | CRUD de perfil do negócio planejado; campos adicionais, necessidade de login e vínculo do perfil/análises ao usuário `A CONFIRMAR`. | Persistência, autenticação e autorização. |
| Interface | Primeira seleção por busca e clique no mapa municipal do RJ; escolher abordagem de frontend compatível com Laravel. | Implementação da consulta e do futuro CRUD. |
| Dados externos | Fontes candidatas registradas em [FONTES_DADOS](../FONTES_DADOS.md); verificar cobertura, acesso, licença, atualização e adequação de cada indicador antes de integrar. | Viabilidade dos indicadores e da concorrência. |
| Atualização de fontes | Direção escolhida: agendada, uma fonte por vez, sem chamadas externas em cada consulta. Frequência, retentativas e prazo de validade `A CONFIRMAR`. | Disponibilidade, carga e tratamento de dados defasados. |
| Score | População e cobertura CNPJ são dados mínimos propostos; fórmula, pesos e fatores opcionais precisam de validação acadêmica. Sem mínimos ou fórmula, não apresentar score validado. | Cálculo reproduzível, justificativa e aviso de insuficiência. |

Resolver cada pendência antes da etapa que depende dela. Fontes e fórmula podem permanecer pendentes durante um protótipo de consulta, desde que ele não apresente um score como validado.

## Sequência proposta

### 1. Validar escopo e ambiente

- [x] Documentar recorte RJ, matriz de fontes e fluxograma de atualização/consulta; critérios de score e concorrência ainda dependem de validação acadêmica.
- [ ] Confirmar as decisões que afetam o primeiro modelo de dados.
- [ ] Conferir e preservar o ambiente existente no computador da escola, incluindo migrations ainda não enviadas.
- [ ] Verificar versões instaladas, dependências e execução básica do Laravel.
- [ ] Verificar a configuração do banco sem expor secrets e consultar o estado das migrations.
- [ ] Registrar as decisões aprovadas na documentação pertinente.

Critério de conclusão: escopo inicial explícito e ambiente mínimo validado, com limitações registradas.

### 2. Implementar a base de dados do domínio

- [x] Versionar e aplicar no notebook do trabalho as migrations iniciais de municípios e atividades econômicas por CNAE.
- [x] Criar a migration e o model de fontes de dados; aplicar a migration no banco local da escola.
- [ ] Revisar atributos, cardinalidades e restrições do modelo conceitual.
- [ ] Definir migrations e models ainda necessários para indicadores municipais e estabelecimentos/CNAEs, conforme o recorte aprovado; preservar os catálogos e a tabela de fontes já criados.
- [ ] Incluir relacionamentos necessários, sem converter automaticamente cada classe conceitual em tabela.
- [ ] Criar dados de demonstração identificados como fictícios, caso essa abordagem seja adotada.
- [ ] Validar integridade e relacionamentos em banco de desenvolvimento ou teste.

Critério de conclusão: estrutura reproduzível por migrations, coerente com as regras aprovadas. Avaliar dados existentes e reversibilidade antes de executar mudanças estruturais; não usar reset destrutivo como rotina.

### 3. Construir a primeira consulta

- [ ] Criar seleção de atividade/CNAE e município do RJ por busca e clique no mapa, ambos vinculados ao código IBGE.
- [ ] Validar entradas e consultar indicadores e concorrentes conforme os critérios aprovados.
- [ ] Apresentar resultados e estados de erro, ausência, fonte defasada ou insuficiência de dados; zero concorrentes apenas com cobertura verificada.
- [ ] Definir propriedade/autenticação antes de implementar a persistência do futuro CRUD de perfil do negócio; campos iniciais previstos: atividade/CNAE, município e preferências.
- [ ] Identificar claramente dados demonstrativos, caso utilizados.
- [ ] Validar o fluxo no navegador e os testes relevantes de entrada e seleção de dados.

Critério de conclusão: formulário → consulta → resultado funcionando. Enquanto não houver fórmula aprovada, informar que o score está indisponível.

### 4. Integrar fontes reais e implementar o score

- [ ] Validar fontes, cobertura geográfica e procedência dos dados.
- [ ] Implementar importações agendadas, sequenciais por fonte, com registro de versão/data, tratamento de falhas e sem publicação de carga parcial.
- [ ] Validar o mapeamento do município da Receita para o código IBGE e distinguir contagem zero de falta de cobertura.
- [ ] Implementar fórmula e pesos aprovados, com justificativa baseada nos fatores efetivamente usados.
- [ ] Testar cálculos, limites e insuficiência de dados.
- [ ] Implementar histórico e preservação do contexto da análise, se aprovados.

Critério de conclusão: resultado rastreável, cálculo reproduzível e limitações apresentadas ao usuário.

## Cuidados de continuidade

- Preservar mudanças locais e consultar instruções do projeto antes de editar código.
- Não assumir MySQL apenas porque o repositório está dentro do XAMPP.
- Registrar a configuração de desenvolvimento aprovada (`rightpoint`, host, porta e usuário), mas não copiar a chave da aplicação nem futuras senhas ou tokens para a documentação.
- Não introduzir IA, RAG ou serviços externos sem necessidade definida pelo escopo.
- Atualizar este plano ao concluir etapas, registrando verificações realmente executadas e pendências remanescentes.

## Ponto de retomada

Na próxima sessão ou em outro computador, começar pela conferência do Git, do Laravel e do banco local conforme o [STATUS.md](../STATUS.md) e o [procedimento para múltiplos computadores](../procedimentos/ambiente-multiplos-computadores.md). Na escola, a migration de `fontes_dados` foi aplicada em 05/10/2026; no outro computador, consultar `php artisan migrate:status` antes de aplicá-la. A próxima tarefa é revisar com o grupo/professora as propostas da [matriz](../arquitetura/matriz-dados-diagnostico.md), selecionar conjuntos verificáveis das [fontes candidatas](../FONTES_DADOS.md) e só então definir as tabelas de indicadores municipais e estabelecimentos. O fluxo de consulta, o mapa, o CRUD e o score continuam pendentes.
