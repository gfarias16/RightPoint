# Status de continuidade — RightPoint

Atualizado em 05/10/2026. Este arquivo registra o ponto de retomada entre escola e trabalho. Antes de cada commit relevante, confira se ele ainda descreve o estado real do projeto.

## Como trabalhamos

- O usuário desenvolve o código na maior parte das vezes. O agente orienta com arquivos, trechos de código e comandos, e revisa o resultado quando solicitado.
- Não considere uma etapa concluída apenas porque ela foi sugerida nesta conversa. Verifique os arquivos, o Git e o banco do computador em uso.
- Consulte [AGENTS.md](../AGENTS.md), o [plano de implementação](./planos/implementacao-inicial.md) e o [procedimento para múltiplos computadores](./procedimentos/ambiente-multiplos-computadores.md) antes de mudanças relevantes.

## Estado do projeto e dos ambientes

- Em 05/10/2026, o notebook do trabalho estava na branch `main`, alinhada a `origin/main` no commit `d7398f2`. O usuário também alterou `.env` e `.env.example` localmente; preservar e revisar essas mudanças antes de publicar.
- A aplicação Laravel fica em `Right_Point/`. A mudança de nome de `Rigth_Point/` foi intencional.
- O commit `84c35d8` incluiu a renomeação, os models `Municipio` e `AtividadeEconomica`, as migrations dos dois catálogos, o `.env.example` e, por decisão explícita do usuário, o `.env` real. O commit posterior `a706b1e` criou este arquivo de status.
- `Right_Point/database/migrations/2026_10_01_230511_create_municipios_table.php` cria `municipios` com `codigo_ibge` único, `nome` e `uf`.
- `Right_Point/database/migrations/2026_10_01_230556_create_atividades_economicas_table.php` cria `atividades_economicas` com `codigo_cnae` único, `nome` e `descricao` opcional.
- Um registro anterior de 01/10/2026 informa que as cinco migrations haviam sido aplicadas e os dois testes iniciais passaram, mas não identifica com segurança qual máquina foi usada. No notebook do trabalho, o banco `rightpoint` estava vazio em 05/10/2026; as mesmas cinco migrations foram aplicadas, `migrate:status` mostrou todas como `Ran` e os dois testes iniciais passaram. Não presuma que esse estado continue válido sem conferir o banco da máquina em uso.
- Na escola, em 05/10/2026, `git status --short --branch` mostrou `main` alinhada a `origin/main` antes das alterações atuais, e `php artisan migrate:status` confirmou as cinco migrations como `Ran`. Os models `Municipio` e `AtividadeEconomica` receberam `$table` e `$fillable` correspondentes às migrations; ambos passaram na checagem de sintaxe e na leitura de tabela, campos e contagem. As duas tabelas estavam vazias nessa verificação.
- Na escola, foi criada a migration `2026_10_06_003411_create_fonte_dados_table.php` e o model `FonteDado`. A migration cria `fontes_dados` com código único, nome e URL de referência; o nome da tabela foi alinhado ao model antes da execução. `php artisan migrate --pretend` mostrou somente essa criação, e `php artisan migrate` a aplicou ao banco local `rightpoint`. As seis migrations aparecem como `Ran`. A tabela ainda não tem carga de fontes confirmada.
- A checagem de sintaxe dos arquivos PHP envolvidos passou e `php artisan test` passou nos dois testes iniciais do Laravel. `php artisan db:table fontes_dados` reconheceu a tabela, mas não concluiu a exibição porque a extensão PHP `intl` não está habilitada neste ambiente; isso não impediu a migration nem os testes.
- A rota principal ainda usa a página `welcome` do Laravel. Não há formulário, consulta de potencial, score, seeders dos catálogos ou testes específicos das regras do RightPoint.
- O usuário registrou seis [fontes candidatas](./FONTES_DADOS.md). Duas URLs públicas do IBGE foram consultadas fora do Laravel em 05/10/2026: uma retornou 92 municípios do RJ; a outra retornou a população residente do município do Rio de Janeiro em 2022. Na escola, o comando experimental `php artisan ibge:consultar-rio` também retornou Rio de Janeiro (`3304557`), `6211223` pessoas e ano `2022`, após execução com acesso à rede. O comando somente lê dados externos; não há importação no banco nem uso desses dados no fluxo de consulta. A seção de CNPJ não contém endpoint.
- O banco de desenvolvimento padronizado é `rightpoint` (minúsculas). No notebook do trabalho, `.env` e `.env.example` usam MySQL em `127.0.0.1:3306`, usuário `root` e senha vazia. O `.env` está rastreado no Git e contém a chave da aplicação; a alteração local do `.env.example` também preencheu `APP_KEY`. A decisão de compartilhar essa chave é do usuário: revise o diff antes de publicar e não copie o valor para esta documentação.
- `vendor/`, `node_modules/` e o banco local não são sincronizados pelo Git. `vendor/` foi instalado no notebook do trabalho em 05/10/2026; o frontend ainda não foi validado ali. Um registro de 01/10/2026 informa falha de instalação npm com `UNABLE_TO_VERIFY_LEAF_SIGNATURE`, sem identificação segura da máquina.

## Próximo passo de desenvolvimento

Os models iniciais, a tabela `fontes_dados` e o primeiro [experimento de leitura com o IBGE](./FONTES_DADOS.md) foram concluídos. A próxima etapa é validar quais arquivos e indicadores das fontes candidatas serão usados, depois modelar `indicadores_municipais` e, para concorrência, `estabelecimentos` e sua associação com CNAEs. Antes de importar municípios ou CNAEs, definir fonte, recorte, método de carga e atualização. Fórmula e histórico do score continuam **A CONFIRMAR**.

Ao retomar, confirmar `git status --short --branch` e `php artisan migrate:status` no computador em uso. No outro computador, a migration de `fontes_dados` estará pendente até ser aplicada ao banco local. Consultar [FONTES_DADOS.md](./FONTES_DADOS.md) para distinguir fontes apenas pesquisadas das efetivamente testadas. O comando `php artisan ibge:consultar-rio` continua somente de leitura.

## Ao abrir em outro computador

1. Antes de atualizar ou clonar, preservar qualquer pasta antiga e conferir `git status --short --branch`, os arquivos em `Right_Point/database/migrations/` e a existência de `.env`. Não sobrescrever trabalho ou configuração local.
2. Após receber os commits, entrar em `Right_Point/`, instalar dependências ausentes com `composer install` e conferir se o `.env` aponta para o banco local `rightpoint`; ajustar porta ou credenciais localmente somente se necessário, revisando o diff antes de publicar.
3. Consultar `php artisan migrate:status`; aplicar `php artisan migrate` apenas se houver migrations pendentes no banco correto. Não usar `migrate:fresh` ou outro reset destrutivo.
4. Depois dessa conferência, revisar os models e o comando experimental do IBGE antes de avançar para a decisão sobre a carga de dados. O estado do banco, as dependências e possíveis arquivos locais de cada computador devem ser verificados novamente.
