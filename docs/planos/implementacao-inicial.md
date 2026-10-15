# Plano de implementação inicial — RightPoint

Última atualização: 14/10/2026 (catálogos de municípios e CNAEs carregados no banco da escola; busca HTTP de CNAEs criada, interface ainda não integrada; população ainda não persistida).

## Objetivo

Dar continuidade à base Laravel e construir, em etapas verificáveis, o fluxo de consulta de potencial comercial por atividade econômica e área geográfica. Este plano registra a proposta de sequência; não transforma decisões pendentes em requisitos aprovados.

## Estado confirmado

- Repositório local: `C:\xampp\htdocs\RightPoint`.
- Aplicação Laravel: `Right_Point/` (renomeação intencional confirmada pelo usuário em 01/10/2026).
- O usuário informou que criou o projeto Laravel para o RightPoint.
- `Right_Point/composer.json` declara PHP `^8.2` e Laravel `^12.0`.
- `Right_Point/routes/web.php` contém a página de exploração, a consulta demonstrativa e a rota intermediária da malha do RJ.
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

Em 14/10/2026, o usuário priorizou uma demonstração visual para a professora e autorizou o agente a implementá-la. Foram criadas a tela de exploração dos municípios do RJ, consulta demonstrativa e perfis no armazenamento local do navegador, sem login. O mapa oferece modo escuro padrão, claro e satélite; o clique na malha do IBGE e a busca de municípios usam `codigo_ibge`. O usuário confirmou que a tela abre e solicitou um mapa maior e zoom pela roda; o código foi ajustado, mas a interação ainda precisa de teste manual. O CRUD compartilhado no banco depende da decisão sobre autenticação/propriedade dos perfis. O catálogo de 1.332 CNAEs foi observado no banco da escola e uma rota de busca local por nome/código foi criada. A interface ainda pede o código digitado; próxima ação: integrar a busca ao formulário, validar as interações no navegador e manter o código como identificador interno. Sem cobertura CNPJ ou fórmula aprovada, o resultado não mostra score ou concorrentes fictícios.

As migrations iniciais e os models dos catálogos estão disponíveis. Em 13/10/2026, o comando `ibge:importar-municipios-rj` passou a gravar os dados com transação e `upsert`; duas execuções reais no MySQL/MariaDB do XAMPP mantiveram 92 municípios do RJ, sem duplicatas e com IDs e datas de criação preservados. Em 14/10/2026, o usuário executou uma consulta exploratória de população, validou seus 92 códigos e valores numéricos de 2022 contra o catálogo local e enviou captura dos metadados que confirma variável 93 como `População residente`, unidade `Pessoas`, no Censo 2022. O usuário criou o model e a migration de indicadores municipais; após revisão, `php -l` passou, `php artisan migrate:status` mostrou a migration como `Ran` no lote `[3]`, e uma consulta de leitura confirmou a tabela no banco `rightpoint`, ainda sem indicadores. A tabela `fontes_dados` existe para rastrear a origem dos dados futuros, mas seu preenchimento continua pendente. A [matriz e o fluxograma](../arquitetura/matriz-dados-diagnostico.md) documentam o recorte inicial do RJ e critérios propostos, sem validar a fórmula. A instalação npm e as próximas estruturas do domínio continuam pendentes.

### Decisão de persistência dos indicadores (14/10/2026)

Para a primeira versão, foi escolhida uma linha atual por **município + fonte + indicador + período de referência**. Uma nova importação do mesmo conjunto deverá atualizar essa linha, sem duplicá-la; períodos diferentes permanecem em linhas diferentes. A migration proposta para `indicadores_municipais` terá vínculo obrigatório com `municipios` e `fontes_dados`, código do indicador, valor, unidade, período de referência, data/hora da coleta, versão da fonte quando disponível e timestamps. Uma restrição única composta sustentará essa regra. A inclusão de uma fonte concreta em `fontes_dados` e a importação dos valores são etapas posteriores; seeders não são necessários para criar a tabela.

Essa tabela não é um histórico de todas as importações. Caso análises sejam persistidas futuramente, os valores e a procedência efetivamente usados deverão ser preservados em um registro/snapshot próprio, para que reimportações não alterem análises antigas. A versão/data da base precisará estar identificada antes de usar o indicador em uma análise, conforme a RI-010; a data da coleta local não a substitui. Persistir análises na primeira versão, política de retenção, frequência de atualização e identificação exata da versão da fonte continuam **A CONFIRMAR**. Nenhum score será tratado como validado antes da aprovação da fórmula e da cobertura dos dados mínimos.

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
- [x] Validar e preparar em memória os 92 municípios do RJ retornados pelo IBGE no notebook da escola, sem gravar no banco.
- [x] Implementar a importação sem duplicatas em `municipios`; duas execuções reais no MySQL local em 13/10/2026 mantiveram 92 linhas, códigos distintos, IDs e datas de criação.
- [x] Criar e revisar o comando de importação das 1.332 subclasses CNAE; o banco da escola contém 1.332 códigos distintos em 14/10/2026. O usuário informou que repetiu o comando; uma consulta posterior confirmou a mesma contagem e os mesmos limites de ID, sem duplicação observável.
- [x] Criar e revisar o arquivo da migration de `indicadores_municipais`; sintaxe e SQL simulado conferidos em 14/10/2026, sem aplicar ao banco.
- [x] Criar e revisar o model `IndicadorMunicipal`; `$fillable` e os vínculos pertencentes a município e fonte foram conferidos, sem testes de persistência ainda.
- [x] Aplicar a migration de `indicadores_municipais` no banco local `rightpoint` e confirmar o estado `Ran` no lote `[3]`.
- [x] Preparar em memória os 92 valores de população de 2022 associados ao ID interno de cada município; conferência concluída no terminal do usuário em 14/10/2026, ainda sem fonte/data da coleta ou gravação.
- [ ] Revisar atributos, cardinalidades e restrições do modelo conceitual.
- [ ] Definir migrations e models ainda necessários para indicadores municipais e estabelecimentos/CNAEs, conforme o recorte aprovado; preservar os catálogos e a tabela de fontes já criados.
- [ ] Incluir relacionamentos necessários, sem converter automaticamente cada classe conceitual em tabela.
- [ ] Criar dados de demonstração identificados como fictícios, caso essa abordagem seja adotada.
- [ ] Avaliar seeders para cadastros fixos e demonstração quando houver dados aprovados; prioridade baixa definida pelo usuário em 14/10/2026, sem bloquear a integração dos indicadores reais.
- [ ] Validar integridade e relacionamentos em banco de desenvolvimento ou teste.

Critério de conclusão: estrutura reproduzível por migrations, coerente com as regras aprovadas. Avaliar dados existentes e reversibilidade antes de executar mudanças estruturais; não usar reset destrutivo como rotina.

### 3. Construir a primeira consulta

- [x] Implementar seleção de CNAE e município do RJ por busca/lista e clique na malha, vinculados ao código IBGE; clique ainda pendente de validação com a malha real.
- [x] Validar formato das entradas e consultar os indicadores já armazenados; concorrentes seguem indisponíveis até integração e cobertura CNPJ.
- [x] Apresentar estados iniciais de erro e insuficiência de dados, sem tratar ausência como zero; estado de fonte defasada ainda pendente.
- [x] Implementar CRUD de perfis local ao navegador sem login, como protótipo explícito. Propriedade/autenticação e persistência no banco continuam pendentes.
- [ ] Identificar claramente dados demonstrativos, caso utilizados.
- [ ] Validar o fluxo inteiro no navegador, inclusive troca entre as três bases cartográficas, seleção por polígono, CRUD local e responsividade. A captura headless confirmou a base escura com 92 limites, mas não cobriu interações.

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

Na próxima sessão ou em outro computador, começar pela conferência do Git, do Laravel e do banco local conforme o [STATUS.md](../STATUS.md) e o [procedimento para múltiplos computadores](../procedimentos/ambiente-multiplos-computadores.md). A gravação dos 92 municípios e dos 1.332 CNAEs foi observada no banco da escola; cada máquina precisa conferir conexão, migrations e dados existentes antes de importar os catálogos localmente. A rota `/cnaes` busca o catálogo local, mas ainda falta conectá-la ao formulário e testar entradas compostas só por espaços. A consulta experimental de população validou códigos e valores numéricos de 2022 contra o catálogo local na execução informada pelo usuário; model e migration existem e a migration foi aplicada no banco da escola, mas a importação idempotente da população continua pendente. A demonstração visual foi implementada em 14/10/2026; a captura confirmou a malha escura, e seleção por clique, alternância de bases e CRUD ainda aguardam validação manual no navegador. Perfis locais não acompanham o Git nem migram entre máquinas. As tabelas de fonte e indicadores ainda estão vazias. As propostas da [matriz](../arquitetura/matriz-dados-diagnostico.md) ainda dependem de validação com o grupo/professora; o score permanece indisponível.
