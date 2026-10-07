# Ambiente local em múltiplos computadores

## Objetivo

Este procedimento explica como manter o RightPoint utilizável no computador da escola e em outros computadores sem confundir arquivos do Git com o estado local do MySQL. O `.env` é uma exceção versionada por decisão explícita do usuário e deve ser tratado como sensível.

Leia este documento antes de instalar dependências, criar o `.env`, executar migrations ou atualizar um ambiente que já possua trabalho local.

## Estado conhecido em 01/10/2026

- O repositório Git está hospedado em `https://github.com/gfarias16/RightPoint.git`.
- A aplicação Laravel está em `Right_Point/`, após renomeação intencional de `Rigth_Point/` confirmada pelo usuário.
- O backend declara PHP `^8.2` e Laravel `^12.0`.
- O banco pretendido é o serviço MySQL compatível fornecido pelo XAMPP e administrado pelo phpMyAdmin.
- `phpMyAdmin` é a interface de administração; o banco efetivo é o serviço MySQL/MariaDB iniciado pelo painel do XAMPP.
- O nome padronizado do banco de desenvolvimento em todas as máquinas é `rightpoint`, em letras minúsculas. No notebook do trabalho, a conexão confirmada em 05/10/2026 é MariaDB 10.4.32 em `127.0.0.1:3306`, usuário `root` e senha vazia. Confira porta e credenciais de cada máquina antes de operar seu banco.
- O `.env.example` contém a configuração MySQL mantida pelo usuário. Por decisão explícita, o `.env` real também foi incluído no Git e contém a chave desta instalação; revise a exposição dessa chave antes de publicar o commit.
- Em um clone novo desta versão, o `.env` virá do Git; `vendor/`, `node_modules` e o banco local não virão.
- O projeto possui as migrations padrão do Laravel e as migrations iniciais de municípios e atividades econômicas por CNAE.

## Registro anterior em 01/10/2026 — máquina a confirmar

- A cópia em `C:\xampp\htdocs\RightPoint` foi atualizada a partir de `6ef8852` antes da renomeação da pasta e das migrations do domínio.
- O `.env` local já existia e foi preservado. Ele apontava para um banco MySQL local chamado `rightpoint`, nome posteriormente padronizado para o projeto.
- PHP 8.2.12, Composer 2.9.7, Node 22.13.0 e Laravel 12.69.2 foram verificados. As dependências PHP já estavam instaladas, e `composer check-platform-reqs` passou.
- O servidor é MariaDB 10.4.32, com collation `utf8mb4_general_ci` no banco local. Antes da preparação, o banco não continha tabelas. As três migrations padrão e as duas migrations de catálogo foram executadas com sucesso e aparecem como `Ran` em `php artisan migrate:status`.
- `php artisan test` passou nos dois testes iniciais do Laravel. Ainda não há testes das regras de negócio do RightPoint.
- `php artisan db:show` falhou porque a consulta interna a `performance_schema.session_status` não encontrou essa tabela nesta instalação do MariaDB. Consultas de leitura e `migrate:status` funcionaram; a falha do comando `db:show` não indica falha da conexão principal.
- O `.env.example` permanece com a configuração MySQL deixada pelo usuário neste computador.
- `node_modules` e `package-lock.json` ainda não existem. `npm.cmd install` falhou primeiro porque o npm estava em modo offline e, ao desativar esse modo apenas no comando, falhou com `UNABLE_TO_VERIFY_LEAF_SIGNATURE`. A cadeia de certificados do acesso ao registro npm precisa ser corrigida para concluir a instalação e validar o build; não desative a verificação TLS.

O registro anterior não identifica de modo confiável se a máquina era a da escola ou a do trabalho. Ele não garante o estado atual de nenhuma das duas. Verifique novamente a pasta, o banco e as migrations antes de operar.

## Conferência do notebook do trabalho em 05/10/2026

- O checkout estava alinhado a `origin/main` no commit `d7398f2`. As alterações locais em `.env` e `.env.example` são intencionais e foram preservadas.
- PHP 8.2.12, MariaDB 10.4.32 e Laravel 12.69.2 foram verificados. `vendor/` foi instalado com `composer install`, respeitando `composer.lock`; neste notebook, o Composer não estava no `PATH` e foi usado um PHAR oficial temporário.
- O MariaDB do XAMPP falhou em uma tentativa inicial de inicialização com uma asserção InnoDB. Depois, o servidor passou a escutar na porta 3306 e respondeu normalmente. Não houve reparo nem alteração no diretório de dados do XAMPP; se a falha voltar, preserve os bancos e investigue antes de restaurar ou apagar qualquer arquivo.
- O banco `rightpoint` existia e não continha tabelas. As três migrations padrão e as duas de catálogo foram executadas, todas aparecem como `Ran` em `php artisan migrate:status`, e os dois testes iniciais passaram.
- Não foi instalado `node_modules/` nem executado build do frontend neste notebook. Uma tentativa de inicializar um banco isolado para diagnóstico foi descartada quando o servidor do XAMPP ficou acessível; o diretório temporário criado foi removido, sem tocar no banco do XAMPP.

## O que o Git sincroniza

Devem ser versionados:

- código-fonte;
- arquivos de migration;
- models, controllers, views e testes;
- seeders e factories que contenham apenas dados adequados ao repositório;
- documentação;
- `.env.example`, revisado antes de publicar; a alteração local de 05/10/2026 inclui `APP_KEY` por decisão do usuário e deve ser tratada como sensível;
- `.env` real, por decisão explícita do usuário em 01/10/2026; trate seu conteúdo como sensível;
- `composer.lock` e, quando gerado, `package-lock.json`.

Não devem ser versionados:

- `vendor/`;
- `node_modules/`;
- banco local do XAMPP;
- dumps contendo dados ou informações sensíveis;
- outras chaves, tokens ou senhas além da chave já presente no `.env` versionado e da alteração intencional do `.env.example` ainda pendente de publicação.

O `.gitignore` ainda contém `.env`, mas ele foi adicionado explicitamente ao índice e já é rastreado: alterações futuras nesse arquivo aparecerão no Git. `vendor/` e `node_modules` continuam ignorados e precisam ser instalados em cada computador.

## Migrations: arquivos versus execução

Há duas operações diferentes:

1. `php artisan make:migration ...` cria um arquivo em `database/migrations/`. Esse arquivo deve ser revisado, commitado e enviado ao GitHub.
2. `php artisan migrate` aplica os arquivos disponíveis ao banco local. As tabelas criadas e o registro local da tabela `migrations` não são enviados ao GitHub.

Se uma migration foi criada no computador da escola, mas não aparece no GitHub, ela provavelmente não recebeu commit e push. Se apenas `php artisan migrate` foi executado, nenhum arquivo novo deveria ser enviado: somente o banco local foi alterado.

## Retomada no computador da escola

### 1. Preservar o ambiente existente

Se a pasta antiga do projeto ainda existir, não a apague e não clone por cima dela. Primeiro verifique:

```powershell
git status --short --branch
git branch --show-current
Get-ChildItem database\migrations
Test-Path .env
```

Execute esses comandos dentro de `Right_Point/`, com exceção dos comandos Git, que também podem ser executados na raiz do repositório.

Antes de baixar atualizações, procure:

- arquivos modificados ainda sem commit;
- migrations que existam somente na escola;
- seeders, models ou configurações ainda não enviados;
- o `.env` já configurado;
- o banco criado no XAMPP.

Não use `git reset --hard`, `git clean`, exclusão da pasta ou sobrescrita do `.env` para resolver divergências. Preserve primeiro o trabalho local e faça commits com caminhos explícitos.

### 2. Verificar o ambiente Laravel já configurado

Com o Apache e o MySQL iniciados no XAMPP, verifique:

```powershell
php -v
composer --version
php artisan about
php artisan migrate:status
```

Se `php` não estiver no `PATH`, use o executável do XAMPP:

```powershell
& 'C:\xampp\php\php.exe' -v
& 'C:\xampp\php\php.exe' artisan about
& 'C:\xampp\php\php.exe' artisan migrate:status
```

Não publique no chat, nos logs ou na documentação o conteúdo completo do `.env`.

### 3. Conferir o banco usado na escola

No phpMyAdmin, registre para o projeto apenas informações não sensíveis:

- nome do banco;
- versão exibida do MySQL/MariaDB;
- collation do banco;
- tabelas existentes;
- resultado de `php artisan migrate:status`.

Não registre senha. Depois dessa conferência, atualize esta documentação e o `.env.example` com valores de exemplo seguros.

## Preparação de um clone novo

### 1. Instalar dependências

Dentro de `Right_Point/`:

```powershell
composer install
npm.cmd install
```

Use `composer install`, e não `composer update`, para instalar as versões registradas em `composer.lock`.

No PowerShell, `npm.cmd` pode ser necessário quando a política de execução bloquear `npm.ps1`.

### 2. Criar a configuração local

Neste repositório, confira o `.env` recebido pelo Git antes de alterar a configuração local. Não execute `key:generate` automaticamente sobre a chave versionada. Somente se o arquivo realmente não existir em um checkout antigo:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Confira a conexão MySQL antes de usar o banco local. Ao alterar o `.env` rastreado, revise o diff antes de qualquer novo commit:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rightpoint
DB_USERNAME=root
DB_PASSWORD=
```

Esta é a configuração de desenvolvimento confirmada no notebook do trabalho. O nome `rightpoint` deve ser mantido nas outras máquinas; se porta, usuário ou senha forem diferentes, ajuste apenas a configuração local necessária e revise as alterações do `.env` rastreado antes de publicar. A senha vazia é adequada somente para este ambiente local de estudo, não para servidores expostos.

### 3. Criar e validar o banco local

1. Inicie o serviço MySQL pelo painel do XAMPP.
2. Se ainda não existir, crie pelo phpMyAdmin um banco vazio chamado `rightpoint`.
3. Confirme que o `.env` aponta para esse banco.
4. Execute:

```powershell
php artisan config:clear
php artisan migrate
php artisan migrate:status
php artisan test
npm.cmd run build
```

Se `php` não estiver no `PATH`, substitua `php` nos comandos pelo caminho explícito `& 'C:\xampp\php\php.exe'`.

Não use `migrate:fresh`, `db:wipe` ou comandos equivalentes sem verificar se o banco contém dados que precisam ser preservados.

## Sincronização de estrutura e dados

Para manter os computadores coerentes:

- mudanças de estrutura devem ser feitas por novas migrations versionadas;
- dados acadêmicos reproduzíveis devem ser fornecidos por seeders;
- correções de dados importantes devem usar um procedimento ou comando versionado e revisado;
- alterações manuais realizadas apenas no phpMyAdmin não são reproduzidas automaticamente no outro computador;
- dumps de banco não devem ser usados como mecanismo normal de sincronização nem enviados ao Git sem revisão de conteúdo e necessidade explícita.

O fluxo esperado para receber alterações de banco em outro computador é:

```powershell
git pull
composer install
npm.cmd install
php artisan migrate
php artisan test
```

Antes de `git pull`, confirme que o `git status` não contém trabalho local que possa ser perdido ou gerar conflito.

## Checklist para agentes de IA

Ao retomar o RightPoint em qualquer computador, o agente deve:

1. ler `docs/README.md`, este procedimento e `docs/planos/implementacao-inicial.md`;
2. executar `git status --short --branch` antes de alterar arquivos;
3. preservar mudanças locais e nunca sobrescrever o `.env` existente;
4. verificar a existência de `.env`, `vendor/`, `node_modules` e das migrations;
5. verificar PHP, Composer e Node sem presumir que estejam no `PATH`;
6. consultar `php artisan migrate:status` antes de criar ou executar migrations, quando o Laravel estiver funcional;
7. confirmar nome, versão e estado do banco sem exibir credenciais;
8. comparar migrations locais com as rastreadas pelo Git;
9. não executar operações destrutivas de banco sem autorização explícita;
10. distinguir falhas de ambiente, dependências ausentes e defeitos do código;
11. documentar somente verificações realmente executadas;
12. marcar como `A CONFIRMAR` qualquer dado que não possa ser determinado.

## Retomada após o trabalho na escola em 06/10/2026

O checkout ativo da escola fica em `C:\xampp\htdocs\RightPoint`. O comando `ibge:importar-municipios-rj` foi executado pelo usuário ali e preparou 92 registros, mas não gravou no banco; a última consulta local mostrou 0 linhas em `municipios`. Código e documentação seguirão pelo Git após o push do usuário, enquanto esse estado do banco não será transferido.

Em casa, antes de qualquer `git pull`, confira a pasta existente, `git status --short --branch` e alterações locais, inclusive no `.env` rastreado. Depois da sincronização, entre em `Right_Point/`, confirme PHP e dependências, inicie o MySQL local e consulte `php artisan migrate:status` e o conteúdo da tabela `municipios` sem apagar dados. Se as dependências PHP estiverem ausentes, use `composer install` com o `composer.lock`. A etapa seguinte está detalhada em [STATUS.md](../STATUS.md): terminar e revisar a gravação idempotente dos 92 municípios, executar o comando duas vezes no banco local correto e verificar que não houve duplicação. O código ainda não realiza essa gravação.

