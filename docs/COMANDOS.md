# Comandos do RightPoint

Execute na pasta `Right_Point/`.

As consultas ao IBGE usam automaticamente `storage/app/ibge-ca-bundle.pem` quando esse pacote CA local existe. Se ele não existir, usam a verificação TLS padrão do PHP. Veja o [procedimento de HTTPS](./procedimentos/ambiente-multiplos-computadores.md#acesso-https-ao-ibge-com-cadeia-incompleta) se ocorrer erro de certificado; não é necessário acrescentar opções `php -d` a cada comando.

## Aplicação

- `php artisan serve` — inicia a interface local.
- `php artisan migrate:status` — mostra quais migrations foram aplicadas neste banco.
- `php artisan migrate --pretend` — mostra o SQL das migrations pendentes sem executá-lo no banco; revisão não equivale a aplicação.
- `php artisan make:model ContagemEstabelecimento -m` — gera model e migration juntos; usar somente antes de os arquivos existirem. O `-m` não aplica a migration no banco.
- `php artisan make:command ImportarContagemCnpj --command=cnpj:importar-contagem` — gera a estrutura do comando com nome de execução definido; ainda precisa ser preenchida antes de importar dados.
- `php artisan test` — executa os testes automatizados.
- `GET /cnaes?busca=4929902` — busca até 20 CNAEs por código ou trecho do nome no banco local; o formulário já usa essa rota.
- `GET /consulta-demonstrativa?codigo_ibge=3302700&cnae=5611201` — lê população e a coleta agregada mais recente do recorte local, sem API externa ou gravação. `concorrentes` é NULL quando não existe coleta; quando existe, objeto com quantidade, critério, fonte/código/URL, consulta UTC e período/versão nullable. Score continua NULL e avisos explicam limites.
- `php artisan test --compact tests/Feature/ConsultaDemonstrativaTest.php` — 11 casos de consulta com SQLite na memória; não altera o MySQL. Suíte completa na entrega de 28/10/2026: 85 testes e 447 asserções aprovados.

## Dados do IBGE

- `php artisan ibge:consultar-rio` — consulta município e população do Rio de Janeiro; não grava dados.
- `php artisan ibge:importar-municipios-rj` — valida e grava/atualiza os 92 municípios do RJ no banco local.
- `php artisan ibge:verificar-populacao-rj` — confere a população de 2022 dos 92 municípios; não grava dados.
- `php artisan ibge:importar-populacao-rj` — valida e grava/atualiza a população do Censo 2022 para os 92 municípios no banco local, com fonte e data de coleta.
- `php artisan ibge:verificar-cnaes` — confere os códigos e nomes de CNAE do IBGE; não grava dados. Requer acesso à API.
- `php artisan ibge:importar-cnaes` — valida as 1.332 subclasses CNAE recebidas do IBGE e grava/atualiza `atividades_economicas` no banco local; requer acesso à API.

## Preparação dos dados CNPJ

- `php artisan cnpj:importar-contagens-rj "caminho/do/lote.json"` — valida e simula, sem gravar. Compartilha `ValidadorContagensRj` com o verificador; exige hash LF/instante UTC da fotografia em `config/contagem_cnpj_rj.php`. Outro conteúdo não é associado ao job antigo. O JSON original é ignorado pelo Git e precisa ser transportado separadamente.
- `php artisan cnpj:importar-contagens-rj "caminho/do/lote.json" --gravar` — grava fonte/coletas em uma transação; preserva piloto e metadados existentes. Mesma coleta/valores não duplica; divergência aborta sem sobrescrever. Testado em memória; primeira escrita no MySQL real fica com o usuário após conferir banco/migration/simulação. Sem API/BigQuery.
- `php artisan test --compact tests/Feature/ImportarContagensRjTest.php` — 14 testes em memória de simulação, escrita, idempotência, rollback e validação. Suíte completa da entrega: **85 testes, 447 asserções aprovados**; não modifica o MySQL.
- `php artisan cnpj:verificar-contagens-rj "caminho/do/lote.json"` — confere o lote completo de 92 municípios RJ/5611201/Ativa e seus vínculos explícitos Receita↔IBGE. Substituir pelo caminho real do JSON exportado; não renomeia, importa ou acessa API. Valida contrato, limites, controles e nomes (exceção Parati/Paraty), exibe tabela/soma/hash e termina em simulação. Sem `--gravar`; rejeita lote incompleto em vez de completar ausência com zero. Arquivo limitado a 1 MiB; exige PHP 64 bits e catálogos locais.
- `php artisan test --compact tests/Feature/VerificarContagensRjTest.php` — 41 testes do novo verificador, com SQLite na memória e JSON fictício temporário; não usa o arquivo real, API ou MySQL. Suíte completa desta entrega: 85 testes, 447 asserções aprovados.
- `php artisan cnpj:importar-contagem` — valida o JSON agregado local do piloto (620/620/0), limites e hash, consulta os catálogos locais e simula sem gravar. Exige o arquivo `storage/app/amostras/contagem-marica-5611201.json`, ignorado pelo Git.
- `php artisan cnpj:importar-contagem --gravar` — grava uma coleta agregada de Maricá/CNAE `5611201`, com fonte e instante UTC fixos deste piloto. Primeira carga e repetição sem duplicação conferidas em 28/10/2026. Mesma coleta com contagens divergentes é rejeitada pelo código, não sobrescrita. Não importa cadastros individuais nem consulta API/BigQuery; confirmar banco e arquivo antes de usar em outra máquina.
- `php artisan cnpj:verificar-municipio 5853 RJ` — traduz o código Receita pelo arquivo `config/municipios_receita.php` e localiza Maricá no MySQL por IBGE `3302700` + UF. Mostra o ID deste banco, sem fixá-lo ou gravar dados. Configuração ampliada em 28/10/2026 para 92 pares RJ; o comando continua consultando um município por execução. Código desconhecido, UF divergente, município ausente ou erro de banco retornam falha. A UF é opcional e tem padrão RJ. Não lê a amostra nem importa estabelecimentos. Se o cache de configuração já estiver ativo após o pull, atualize com `php artisan config:cache` antes de testar.
- `php artisan test --compact tests/Feature/VerificarMunicipioReceitaTest.php` — executa somente os seis testes do vínculo municipal com leituras simuladas; não abre banco, não usa SQLite nem executa migrations. Não equivale a testar a suíte inteira.
- `php artisan cnpj:verificar-amostra` — confere a lista JSON local `storage/app/amostras/cnpj-marica-5611201.json`, campos textuais, CNPJ numérico de 14 dígitos, filtros RJ/Receita `5853`/CNAE principal `5611201`/`Ativa` e duplicatas. Não valida dígitos verificadores nem total de concorrentes; não usa API ou grava no banco. O arquivo local não acompanha o Git.
- `php artisan cnpj:verificar-amostra "caminho/do/arquivo.json"` — confere outro arquivo local, sem alterar a amostra padrão. Com `storage/app/amostras/teste-duplicado.json`, o teste fictício foi rejeitado no registro 2 com código de saída 1; `$LASTEXITCODE` no PowerShell mostra esse código logo após a execução.
- `php artisan cnpj:verificar-busca 3302700 5611201` — consulta uma página de até 2 registros da Minha Receita; confere campos, CNPJ textual preenchido, duplicatas e filtros, sem gravar ou calcular concorrentes.
- `php artisan cnpj:verificar-busca 3302700 5611201 --paginas=2` — percorre até 2 páginas com os mesmos filtros; aceita de 1 a 3 e para em fim, erro, HTTP 429 ou repetição de CNPJ/cursor. O resultado é uma amostra.
- `php artisan cnpj:verificar-busca 3302700 5611201 --cursor='CURSOR_RECEBIDO'` — consulta a próxima página usando o cursor completo da resposta anterior e os mesmos filtros; uma chamada por execução. Substitua `CURSOR_RECEBIDO` pelo valor real.
- `php artisan cnpj:verificar-estabelecimentos "caminho/do/arquivo"` — confere arquivo existente/legível, lê uma linha CSV separada por `;`, fecha o arquivo e mostra a quantidade de campos. Caminho inválido ou primeira linha vazia retorna falha. Ainda não valida o leiaute da Receita nem grava no banco. Para a amostra técnica movida pelo usuário, o caminho é `storage/teste-leitura.csv`, relativo a `Right_Point/`; a execução retornou 2 campos.
