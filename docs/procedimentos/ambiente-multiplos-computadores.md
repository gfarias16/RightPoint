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

## Retomada após a importação em 13/10/2026

O checkout ativo fica em `C:\xampp\htdocs\RightPoint`. Em 13/10/2026, o agente implementou e executou a importação a pedido do usuário: o MySQL/MariaDB local passou de 0 para 92 municípios do RJ. A segunda execução preservou a quantidade, os códigos únicos, os IDs e as datas de criação. O comando usa transação e atualiza nome e UF pelo código IBGE, além de `updated_at`. Código e documentação podem seguir pelo Git, enquanto as linhas do banco continuam locais.

Em casa, antes de qualquer `git pull`, confira a pasta existente, `git status --short --branch` e alterações locais, inclusive no `.env` rastreado. Depois da sincronização, entre em `Right_Point/`, confirme PHP e dependências, inicie o MySQL local e consulte `php artisan migrate:status` e o conteúdo da tabela `municipios` sem apagar dados. Se as dependências PHP estiverem ausentes, use `composer install` com o `composer.lock`; aplique somente migrations pendentes no banco correto.

Depois de conferir que a conexão aponta para o MySQL local `rightpoint`, execute dentro de `Right_Point/`:

```powershell
php artisan ibge:importar-municipios-rj
```

No phpMyAdmin, selecione o banco `rightpoint` e execute na aba SQL:

```sql
SELECT COUNT(*) AS total, COUNT(DISTINCT codigo_ibge) AS codigos_distintos
FROM municipios
WHERE uf = 'RJ';
```

O resultado esperado é 92 em ambas as colunas para o catálogo atual do RJ. Repita a importação e a consulta para confirmar que não surgem duplicatas. Os IDs existentes devem permanecer; `updated_at` pode mudar a cada importação. Se já houver registros extras, investigue a origem antes de excluir qualquer dado. A falha de gravação retorna erro e registra a exceção no log do Laravel. Consulte o [STATUS.md](../STATUS.md) para o próximo exercício e registre as verificações feitas nessa máquina.

## População do Censo 2022 em outra máquina

Em 20/10/2026, o notebook do trabalho recebeu 92 indicadores de população de 2022, um por município do RJ, com fonte e data. **O Git transporta o importador, não as linhas do MySQL.** Na escola ou em casa, preserve mudanças locais antes do pull, confirme a conexão com o banco correto e execute `php artisan migrate:status` dentro de `Right_Point/`. A migration de `indicadores_municipais` deve aparecer como `Ran`; aplique somente migrations pendentes após conferir seu conteúdo. Não use `migrate:fresh`.

Antes de importar, confira no phpMyAdmin do banco `rightpoint`:

```sql
SELECT COUNT(*) AS total, COUNT(DISTINCT municipio_id) AS municipios
FROM indicadores_municipais
WHERE codigo_indicador = 'populacao_residente'
  AND periodo_referencia = '2022';
```

Se a tabela estiver vazia, mas os 92 municípios já estiverem carregados, execute no terminal da aplicação:

```powershell
php artisan ibge:verificar-populacao-rj
php artisan ibge:importar-populacao-rj
```

O primeiro comando consulta e valida sem gravar. Só execute o segundo se o primeiro confirmar os 92 municípios. Confira novamente a consulta SQL: o esperado é `total = 92` e `municipios = 92`. Se já houver dados, examine-os antes de reimportar; o importador usa `upsert`, mas a repetição ainda não foi observada neste notebook. Registre o resultado local em `docs/STATUS.md`. O pacote CA local do IBGE, se necessário, é tratado na seção de HTTPS abaixo e também não acompanha o Git.

## Acesso HTTPS ao IBGE com cadeia incompleta

Na preparação da escola em 20/10/2026, os verificadores e as cargas de CNAEs e população funcionaram com a verificação TLS padrão, sem criar um pacote CA adicional. A solução abaixo é histórica e deve ser usada somente após confirmar a necessidade no ambiente atual.

Em 14/10/2026, no computador da escola, o PHP/cURL retornou erro 60 ao consultar `servicodados.ibge.gov.br`. O OpenSSL recebeu apenas o certificado do servidor e não o intermediário. O pacote CA padrão do XAMPP é de 2022. A solução abaixo funcionou para verificar CNAEs e permitiu importar municípios e CNAEs nesse banco. Em outros ambientes, confirme primeiro o emissor e a cadeia observados, pois podem ser diferentes.

No PowerShell, dentro de `Right_Point/`, consulte o emissor do certificado recebido:

```powershell
'' | & 'C:\xampp\apache\bin\openssl.exe' s_client -connect servicodados.ibge.gov.br:443 -servername servicodados.ibge.gov.br 2>$null |
    & 'C:\xampp\apache\bin\openssl.exe' x509 -noout -subject -issuer
```

Se o emissor for `Sectigo Public Server Authentication CA DV R36`, baixe o [pacote CA do curl](https://curl.se/ca/cacert.pem) e o [intermediário R36 indicado pela Sectigo](https://www.sectigo.com/knowledge-base/detail/Sectigo-Public-Intermediates-and-Roots). Os arquivos ficam em `storage/app/`, ignorada pelo Git:

```powershell
Invoke-WebRequest 'https://curl.se/ca/cacert.pem' -UseBasicParsing -OutFile 'storage/app/cacert.pem'
Invoke-WebRequest 'https://crt.sh/?d=4267304690' -UseBasicParsing -OutFile 'storage/app/sectigo-r36.pem'
& 'C:\xampp\apache\bin\openssl.exe' x509 -in 'storage/app/sectigo-r36.pem' -noout -subject -issuer
& 'C:\xampp\apache\bin\openssl.exe' verify -CAfile 'storage/app/cacert.pem' 'storage/app/sectigo-r36.pem'
```

Prossiga apenas se o certificado baixado tiver o sujeito `Sectigo Public Server Authentication CA DV R36`, o emissor `Sectigo Public Server Authentication Root R46` e a última verificação terminar em `OK`. Em caso de falha, investigue a origem do certificado; não desabilite a validação TLS nem instale uma autoridade desconhecida.

```powershell
Get-Content 'storage/app/cacert.pem','storage/app/sectigo-r36.pem' |
    Set-Content 'storage/app/ibge-ca-bundle.pem' -Encoding ascii
php artisan ibge:verificar-cnaes
```

O código em `app/Support/IbgeHttp.php` detecta esse arquivo e o usa apenas nas requisições ao IBGE; se ele estiver ausente, mantém a verificação TLS padrão. Em 14/10/2026, a verificação de CNAEs com o mesmo pacote, passado manualmente por opções `php -d`, exibiu `CNAEs conferidos: 1332`, com acentos corretos e `Nenhum dado foi gravado.` O usuário confirmou depois que a forma curta `php artisan ibge:verificar-cnaes` também funcionou, mas não compartilhou a saída detalhada. O comando de verificação **não importa CNAEs**. Depois de conferir o banco local, `php artisan ibge:importar-municipios-rj` e `php artisan ibge:importar-cnaes` usam o mesmo pacote automaticamente e gravam nos catálogos; na escola, foram observados 92 municípios e 1.332 CNAEs distintos após repetições informadas pelo usuário. Em outro computador, confira a contagem local antes e depois de importar. Não modifique o `php.ini` global para contornar este serviço; a correção definitiva cabe ao servidor que deve fornecer a cadeia completa.

## Configuração local do Apache na escola

Em 20/10/2026, uma requisição apresentou `MissingAppKeyException` apesar de a chave existente ser reconhecida pelo Laravel no terminal. Executar `php artisan config:cache` resolveu a falha na rodada de verificação seguinte: página, JavaScript, busca de CNAEs e consulta responderam HTTP 200 em paralelo. A causa não foi comprovada; acompanhar se a falha retorna. Não foi necessário gerar outra chave ou alterar o `.env`.

O cache em `bootstrap/cache/config.php` é local, ignorado pelo Git e pode conter valores sensíveis. Depois de mudar o `.env` neste ambiente, execute dentro de `Right_Point/`:

```powershell
php artisan config:cache
```

Esse comando reconstrói o cache com os valores atuais. Para voltar a carregar diretamente o `.env`, use `php artisan config:clear`. Confirme a conexão do banco novamente antes de futuras cargas. A aplicação da escola está acessível pelo Apache em `http://127.0.0.1/RightPoint/Right_Point/public/`.

