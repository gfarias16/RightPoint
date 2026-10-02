# Status de continuidade — RightPoint

Atualizado em 01/10/2026. Este arquivo registra o ponto de retomada para outro computador. Antes de cada commit relevante, confira se ele ainda descreve o estado real do projeto.

## Como trabalhamos

- O usuário desenvolve o código na maior parte das vezes. O agente orienta com arquivos, trechos de código e comandos, e revisa o resultado quando solicitado.
- Não considere uma etapa concluída apenas porque ela foi sugerida nesta conversa. Verifique os arquivos, o Git e o banco do computador em uso.
- Consulte [AGENTS.md](../AGENTS.md), o [plano de implementação](./planos/implementacao-inicial.md) e o [procedimento para múltiplos computadores](./procedimentos/ambiente-multiplos-computadores.md) antes de mudanças relevantes.

## Estado confirmado neste computador

- O repositório está na branch `main`, acompanhando `origin/main`. Antes desta atualização documental, `git status --short --branch` não mostrava alterações locais.
- A aplicação Laravel fica em `Right_Point/`. A mudança de nome de `Rigth_Point/` foi intencional.
- O commit `84c35d8` incluiu a renomeação, os models `Municipio` e `AtividadeEconomica`, as migrations dos dois catálogos, o `.env.example` e, por decisão explícita do usuário, o `.env` real. O commit posterior `a706b1e` criou este arquivo de status.
- `Right_Point/database/migrations/2026_10_01_230511_create_municipios_table.php` cria `municipios` com `codigo_ibge` único, `nome` e `uf`.
- `Right_Point/database/migrations/2026_10_01_230556_create_atividades_economicas_table.php` cria `atividades_economicas` com `codigo_cnae` único, `nome` e `descricao` opcional.
- Neste computador, as três migrations padrão do Laravel e as duas de catálogo foram aplicadas ao MariaDB local. Os dois testes iniciais do Laravel passaram na última verificação registrada. Isso não comprova o estado do banco em outro computador.
- Os models existem, mas ainda não receberam `$fillable`. `Municipio` ainda contém apenas o esqueleto gerado; `AtividadeEconomica` já declara a tabela `atividades_economicas`.
- A rota principal ainda usa a página `welcome` do Laravel. Não há formulário, consulta de potencial, score, seeders dos catálogos ou testes específicos das regras do RightPoint.
- O `.env.example` deve permanecer com os valores deixados pelo usuário. O `.env` está rastreado no Git e contém a chave da aplicação: confira alterações nele antes de novos commits e não copie seus valores para esta documentação.
- `vendor/`, `node_modules/` e o banco local não são sincronizados pelo Git. A instalação npm deste computador ainda falha com `UNABLE_TO_VERIFY_LEAF_SIGNATURE`; o build do frontend não foi validado.

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

## Ao abrir em outro computador

1. Antes de atualizar ou clonar, preservar qualquer pasta antiga e conferir `git status --short --branch`, os arquivos em `Right_Point/database/migrations/` e a existência de `.env`. Não sobrescrever trabalho ou configuração local.
2. Após receber os commits, entrar em `Right_Point/`, instalar dependências ausentes com `composer install` e conferir o banco local e a configuração do `.env` sem divulgar valores sensíveis.
3. Consultar `php artisan migrate:status`; aplicar `php artisan migrate` apenas se houver migrations pendentes no banco correto. Não usar `migrate:fresh` ou outro reset destrutivo.
4. Continuar pelos models somente depois dessa conferência. O estado do banco, as dependências e possíveis arquivos locais no outro computador permanecem **A CONFIRMAR**.
