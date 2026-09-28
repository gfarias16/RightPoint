# AGENTS.md — RightPoint

## Escopo

Estas instruções se aplicam a todo o repositório RightPoint. Instruções mais específicas em subdiretórios, se forem criadas no futuro, prevalecem dentro do próprio escopo.

## Visão rápida do projeto

- O RightPoint é um projeto acadêmico que apoia empreendedores na avaliação do potencial comercial de uma atividade em determinada região.
- A entrada prevista inclui atividade econômica, região ou localização e, quando aplicável, raio geográfico.
- A saída prevista inclui score de 0 a 100, concorrentes, indicadores, justificativa e avisos sobre insuficiência de dados.
- O score é orientativo e não representa garantia de sucesso comercial.
- A aplicação Laravel está em `Rigth_Point/`. A grafia atual da pasta contém `Rigth` e deve ser preservada até que uma renomeação seja planejada e autorizada.
- A documentação acadêmica e operacional está em `docs/`.

## Estado atual conhecido

Estado documentado em 28/09/2026:

- backend declarado com PHP `^8.2` e Laravel `^12.0`;
- aplicação ainda próxima do esqueleto inicial do Laravel;
- rota principal ainda retorna a view padrão `welcome`;
- nenhuma funcionalidade de domínio do RightPoint foi confirmada como implementada;
- o repositório contém somente as migrations padrão do Laravel;
- o banco pretendido é o serviço MySQL compatível do XAMPP, administrado pelo phpMyAdmin;
- nome do banco, versão exata MySQL/MariaDB, collation e credenciais permanecem `A CONFIRMAR`;
- o `.env.example` ainda aponta para SQLite e não representa a configuração final do RightPoint;
- `.env`, banco local, `vendor/` e `node_modules/` não são sincronizados pelo Git.

Não trate este resumo como prova do estado atual. Confirme código, Git e ambiente antes de agir, especialmente no computador da escola.

## Primeiras ações em toda retomada

1. Execute `git status --short --branch` antes de alterar qualquer arquivo.
2. Preserve todas as mudanças locais, inclusive arquivos ainda não rastreados.
3. Leia, nesta ordem:
   - `docs/README.md`;
   - `docs/planos/implementacao-inicial.md`;
   - `docs/procedimentos/ambiente-multiplos-computadores.md`.
4. Leia apenas a documentação adicional necessária para a tarefa atual.
5. Verifique se o ambiente observado concorda com a documentação. Registre divergências em vez de escolher automaticamente uma fonte como correta.

## Roteamento de contexto

- Regras e conceitos do domínio: `docs/contexto/`.
- Arquitetura e modelo conceitual: `docs/arquitetura/`.
- Continuidade da implementação: `docs/planos/implementacao-inicial.md`.
- Troca entre computadores e preparação local: `docs/procedimentos/ambiente-multiplos-computadores.md`.
- Segurança: `docs/seguranca/README.md`.
- Interface: `docs/interface/README.md`.
- Enunciado e minimundo originais: arquivos PDF e DOCX em `docs/`.

Não carregue toda a documentação automaticamente. Use divulgação progressiva de contexto conforme a tarefa.

## Retomada no computador da escola

O computador da escola pode conter o `.env`, banco, dependências, migrations ou código que nunca foram enviados ao GitHub.

Antes de clonar novamente, atualizar o repositório ou instalar dependências:

- verifique se a pasta antiga do projeto ainda existe;
- verifique `git status`, branch atual e arquivos não rastreados;
- liste `Rigth_Point/database/migrations/` e compare com os arquivos rastreados pelo Git;
- confirme apenas a existência do `.env`, sem mostrar seu conteúdo completo;
- consulte `php artisan migrate:status` quando o Laravel estiver funcional;
- confirme no XAMPP/phpMyAdmin o nome e a versão do banco sem expor credenciais.

Não clone por cima da pasta antiga, não sobrescreva o `.env` e não descarte mudanças para resolver divergências. Se houver trabalho local, preserve-o antes de qualquer sincronização.

## Ambiente local

- Em um clone novo, a ausência de `.env`, `vendor/` e `node_modules/` é esperada.
- Use `composer install`, não `composer update`, para respeitar `composer.lock`.
- No PowerShell, use `npm.cmd` se `npm.ps1` estiver bloqueado pela política de execução.
- Se `php` não estiver no `PATH` e o XAMPP estiver em seu caminho padrão, verifique o executável `C:\xampp\php\php.exe` antes de concluir que o PHP não está instalado.
- Não presuma que Composer, PHP, Node, MySQL ou Apache estejam configurados apenas porque o projeto foi clonado.
- Não altere `.env.example` com valores reais ou credenciais.

## Banco de dados e migrations

- O phpMyAdmin é uma interface de administração; o banco efetivo é o serviço MySQL/MariaDB do XAMPP.
- Antes de criar migrations do domínio, confirme as decisões pendentes no plano de implementação.
- Arquivos de migration devem ser versionados; a execução de `php artisan migrate` afeta somente o banco local.
- Use novas migrations para evoluir a estrutura e seeders para dados acadêmicos reproduzíveis.
- Alterações manuais feitas apenas no phpMyAdmin não são reproduzidas no outro computador.
- Não execute `migrate:fresh`, `db:wipe`, `DROP`, `TRUNCATE` ou outras operações destrutivas sem autorização explícita e avaliação dos dados existentes.
- Antes de executar migrations, consulte o estado atual e avalie reversibilidade.

## Desenvolvimento e escopo

- Implemente a menor mudança coerente com a tarefa e com o estágio acadêmico do projeto.
- Não transforme automaticamente todas as classes conceituais em tabelas.
- Não invente fórmula de score, pesos, fontes públicas, regras de equivalência de CNAE ou requisitos de autenticação.
- Use `A CONFIRMAR` quando uma decisão depender do grupo ou da professora.
- Não introduza IA, RAG, microsserviços ou integrações externas sem necessidade aprovada.
- Preserve a identidade e os padrões existentes antes de propor novas tecnologias ou arquitetura.
- Escreva documentação em português claro.
- Comentários de código devem explicar intenção, regra de negócio ou decisão não óbvia, e não repetir a sintaxe.

## Segurança

- Nunca leia ou publique o conteúdo completo do `.env` sem necessidade específica.
- Nunca registre senhas, tokens, chaves, strings de conexão ou credenciais em código, documentação, commits ou logs.
- Use exemplos seguros em `.env.example`.
- Trate URLs privadas, dados de conexão e eventuais dados empresariais não públicos como informações sensíveis.

## Git

- Preserve alterações locais e confira o diff antes de editar arquivos já modificados.
- Não use `git add -A` em uma árvore de trabalho mista; prepare caminhos explícitos.
- Não execute automaticamente `push`, `merge`, `rebase`, `reset --hard`, `clean` ou force push.
- Migrations, models, seeders e documentação necessários para reproduzir o projeto devem receber commits semanticamente coerentes.
- Dependências instaladas, `.env` e o banco local não devem ser commitados.

## Validação

Valide proporcionalmente à mudança:

- sintaxe PHP;
- `php artisan about` e `php artisan migrate:status` para diagnóstico do ambiente;
- testes relevantes com `php artisan test`;
- build do frontend com `npm.cmd run build` quando houver alteração correspondente;
- fluxo real no navegador para mudanças significativas de interface.

Se dependências ou configuração impedirem a validação, informe o bloqueio com a evidência observada. Nunca declare que um teste passou quando ele não foi executado.

## Atualização documental

- Atualize o plano quando uma etapa for realmente concluída.
- Atualize limitações quando uma pendência for resolvida ou uma nova limitação surgir.
- Registre decisões relevantes de banco, arquitetura, integração ou score em `docs/decisoes-arquiteturais/` quando forem aprovadas.
- Registre procedimentos reproduzíveis em `docs/procedimentos/`.
- Não crie documentação para alterações triviais.

## Entrega ao usuário

Ao concluir uma tarefa relevante, informe resumidamente:

- o que foi feito e por quê;
- principais arquivos alterados;
- validações realmente executadas;
- riscos, limitações e pendências;
- documentação atualizada ou ainda necessária.
