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
- Os models existem, mas ainda não receberam `$fillable`. `Municipio` ainda contém apenas o esqueleto gerado; `AtividadeEconomica` já declara a tabela `atividades_economicas`.
- A rota principal ainda usa a página `welcome` do Laravel. Não há formulário, consulta de potencial, score, seeders dos catálogos ou testes específicos das regras do RightPoint.
- O usuário registrou seis [fontes candidatas](./FONTES_DADOS). Em 05/10/2026, duas URLs públicas do IBGE foram consultadas fora do Laravel: uma retornou 92 municípios do RJ; a outra retornou a população residente do município do Rio de Janeiro em 2022. Ainda não existe integração no código. A seção de CNPJ não contém endpoint.
- O banco de desenvolvimento padronizado é `rightpoint` (minúsculas). No notebook do trabalho, `.env` e `.env.example` usam MySQL em `127.0.0.1:3306`, usuário `root` e senha vazia. O `.env` está rastreado no Git e contém a chave da aplicação; a alteração local do `.env.example` também preencheu `APP_KEY`. A decisão de compartilhar essa chave é do usuário: revise o diff antes de publicar e não copie o valor para esta documentação.
- `vendor/`, `node_modules/` e o banco local não são sincronizados pelo Git. `vendor/` foi instalado no notebook do trabalho em 05/10/2026; o frontend ainda não foi validado ali. Um registro de 01/10/2026 informa falha de instalação npm com `UNABLE_TO_VERIFY_LEAF_SIGNATURE`, sem identificação segura da máquina.

## Próximo passo de desenvolvimento

Completar os dois models, sem criar nova migration nesta etapa:

1. Em `Right_Point/app/Models/Municipio.php`, substituir o comentário `//` por `protected $table = 'municipios';` e por `$fillable` com `codigo_ibge`, `nome` e `uf`.
2. Em `Right_Point/app/Models/AtividadeEconomica.php`, manter `$table = 'atividades_economicas'` e acrescentar `$fillable` com `codigo_cnae`, `nome` e `descricao`.
3. Na pasta `Right_Point/`, validar sintaxe, estado das migrations e leitura dos models:

```powershell
php -l app\Models\Municipio.php
php -l app\Models\AtividadeEconomica.php
php artisan migrate:status
php artisan tinker
```

No Tinker, verificar `getTable()`, `getFillable()` e `count()` de cada model. Essas consultas não precisam gravar dados. Depois disso, definir se os catálogos serão preenchidos com dados de demonstração identificados como fictícios ou com uma fonte real aprovada, antes de criar seeders.

Em paralelo ou depois dos models, o aluno pode fazer o [primeiro experimento de leitura com o IBGE](./FONTES_DADOS): relacionar município e população pelo código IBGE, sem gravar dados e sem calcular score. Revisar o exercício com o agente antes de escolher como importar ou armazenar indicadores.

## Ao abrir em outro computador

1. Antes de atualizar ou clonar, preservar qualquer pasta antiga e conferir `git status --short --branch`, os arquivos em `Right_Point/database/migrations/` e a existência de `.env`. Não sobrescrever trabalho ou configuração local.
2. Após receber os commits, entrar em `Right_Point/`, instalar dependências ausentes com `composer install` e conferir se o `.env` aponta para o banco local `rightpoint`; ajustar porta ou credenciais localmente somente se necessário, revisando o diff antes de publicar.
3. Consultar `php artisan migrate:status`; aplicar `php artisan migrate` apenas se houver migrations pendentes no banco correto. Não usar `migrate:fresh` ou outro reset destrutivo.
4. Continuar pelos models somente depois dessa conferência. O estado do banco, as dependências e possíveis arquivos locais no outro computador permanecem **A CONFIRMAR**.
