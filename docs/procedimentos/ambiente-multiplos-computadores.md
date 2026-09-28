# Ambiente local em múltiplos computadores

## Objetivo

Este procedimento explica como manter o RightPoint utilizável no computador da escola e em outros computadores sem versionar credenciais ou confundir arquivos do Git com o estado local do MySQL.

Leia este documento antes de instalar dependências, criar o `.env`, executar migrations ou atualizar um ambiente que já possua trabalho local.

## Estado conhecido em 28/09/2026

- O repositório Git está hospedado em `https://github.com/gfarias16/RightPoint.git`.
- A aplicação Laravel está em `Rigth_Point/`. A grafia atual da pasta deve ser preservada até que uma eventual renomeação seja planejada.
- O backend declara PHP `^8.2` e Laravel `^12.0`.
- O banco pretendido é o serviço MySQL compatível fornecido pelo XAMPP e administrado pelo phpMyAdmin.
- `phpMyAdmin` é a interface de administração; o banco efetivo é o serviço MySQL/MariaDB iniciado pelo painel do XAMPP.
- O nome do banco, a versão exata do servidor e as credenciais locais permanecem `A CONFIRMAR`.
- O `.env.example` ainda contém a configuração inicial do Laravel para SQLite e não representa a decisão de banco do RightPoint.
- No clone verificado fora da escola não existem `.env`, `vendor/`, `node_modules` nem banco local configurado.
- O GitHub contém apenas as migrations padrão do Laravel para usuários/sessões, cache e filas. Não existem migrations de domínio do RightPoint versionadas neste momento.

## O que o Git sincroniza

Devem ser versionados:

- código-fonte;
- arquivos de migration;
- models, controllers, views e testes;
- seeders e factories que contenham apenas dados adequados ao repositório;
- documentação;
- `.env.example`, sem credenciais;
- `composer.lock` e, quando gerado, `package-lock.json`.

Não devem ser versionados:

- `.env` e suas credenciais;
- `vendor/`;
- `node_modules/`;
- banco local do XAMPP;
- dumps contendo dados ou informações sensíveis;
- chaves, tokens ou senhas.

O `.gitignore` do Laravel exclui intencionalmente `.env`, `vendor/` e `node_modules`. Portanto, esses itens não aparecem depois de um clone e precisam ser preparados em cada computador.

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

Execute esses comandos dentro de `Rigth_Point/`, com exceção dos comandos Git, que também podem ser executados na raiz do repositório.

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

Dentro de `Rigth_Point/`:

```powershell
composer install
npm.cmd install
```

Use `composer install`, e não `composer update`, para instalar as versões registradas em `composer.lock`.

No PowerShell, `npm.cmd` pode ser necessário quando a política de execução bloquear `npm.ps1`.

### 2. Criar a configuração local

Somente quando ainda não existir `.env`:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Configure localmente a conexão MySQL sem commitar o `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=A_CONFIRMAR
DB_USERNAME=A_CONFIRMAR
DB_PASSWORD=A_CONFIRMAR
```

Os valores `A_CONFIRMAR` são marcadores documentais e não devem ser usados literalmente. Cada computador pode ter credenciais locais diferentes, mas deve utilizar o mesmo nome lógico de banco e a mesma estrutura criada pelas migrations depois que esses valores forem definidos pelo grupo.

### 3. Criar e validar o banco local

1. Inicie o serviço MySQL pelo painel do XAMPP.
2. Crie pelo phpMyAdmin um banco vazio com o nome aprovado pelo grupo.
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

## Diagnóstico atual e próxima conferência

No próximo acesso ao computador da escola, a prioridade é descobrir se há arquivos não enviados e registrar o estado real do banco. Somente depois dessa conferência será seguro ajustar o `.env.example`, instalar o ambiente no outro computador e criar as migrations do domínio.

