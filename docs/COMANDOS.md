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

**Atualização de 04/11 — indicador:** a consulta agora acrescenta `oferta_por_10_mil_habitantes`: `estado` (`calculado`/`indisponivel`), `valor` numérico com arredondamento de duas casas ou NULL, `base_populacional` 10000 e `motivo` textual/NULL. Usa a mesma população/contagem já retornadas e mantém fontes, avisos e score NULL. `php artisan test --compact` aprovou **147 testes/804 asserções** na suíte final; testes do cálculo/integridade usam dados fictícios, não alteram o MySQL. Navegador Apache conferiu exemplos reais e estado sem coleta. Assets públicos não exigem build Vite nesta alteração.

## Score experimental — implementado em 04/11/2026

- `php artisan diagnostico:analisar-sensibilidade` — compara 30/70, 50/50 e 70/30 nos mesmos quatro conjuntos completos. Mostra mudanças de posições em relação à base 50/50; somente leitura, sem rede, importação ou alteração dos pesos da consulta.
- `php artisan diagnostico:analisar-sensibilidade --markdown --no-ansi` — emite relatório completo no terminal, com notas/posições dos 92 municípios por CNAE, hashes, referências e limites. Não escreve arquivo; conferir saída de sucesso antes de salvar. Qualquer conjunto incompleto bloqueia a publicação, sem resultado parcial. [Procedimento](./procedimentos/sensibilidade-score.md).

- A rota de consulta existente agora retorna `score` numérico ou NULL e `diagnostico` com estado `experimental`/`indisponivel`, método, fatores/pesos/contribuições, motivos, referências, premissa CNPJ 2026 e conjunto/hash de dados. Campo de score não é probabilidade.
- Recorte: quatro CNAEs, 92 municípios, população/renda 2022 e fotografia CNPJ fixada em `2026-11-03 20:41:41` UTC. Falta/invalidez em qualquer município bloqueia o conjunto. Fonte alternativa ou coleta nova não são incorporadas silenciosamente. Não há comando de gravação de score, rede externa ou migration nova.
- Testes focados: `php artisan test --compact tests/Unit/CalculadorScoreRelativoTest.php tests/Feature/DiagnosticoPilotoTest.php`. Não alteram o MySQL; usam dados fictícios/SQLite em memória. [Fórmula e integridade](./decisoes-arquiteturais/001-score-piloto-rj.md).

## Renda municipal — integração e carga conferidas em 04/11/2026

- `php artisan ibge:importar-renda-rj` — consulta metadados/valores oficiais, confere tabela 10295/variável média 13431, totais e 92 municípios e simula sem gravar. Requer rede/TLS e catálogo local.
- `php artisan ibge:importar-renda-rj --gravar` — carga explícita/transacional dos 92 rendimentos de 2022, com upsert por município/fonte/indicador/período. Reimportação pode atualizar valores/coleta sem duplicar; população e outras fontes preservadas. Sem migration nova. Na origem, esperado 184 indicadores após primeira carga.
- Consulta acrescenta `renda` NULL sem carga ou objeto com valor/unidade/período/fonte/código/URL/coleta/identificação de recorte. Sem rede durante a consulta. Leitura posterior confirmou 92 rendas/184 indicadores no MySQL; renda integra o método experimental descrito acima.
- `php artisan test --compact tests/Feature/ImportarRendaRjTest.php` — 32 casos novos HTTP falso/SQLite na memória. Suíte completa: **179 testes/954 asserções**. Simulação real preservou banco; escrita MySQL ainda não executada pelo agente. [Procedimento](./procedimentos/renda-municipal-rj.md).

## Dados do IBGE

- `php artisan ibge:consultar-rio` — consulta município e população do Rio de Janeiro; não grava dados.
- `php artisan ibge:importar-municipios-rj` — valida e grava/atualiza os 92 municípios do RJ no banco local.
- `php artisan ibge:verificar-populacao-rj` — confere a população de 2022 dos 92 municípios; não grava dados.
- `php artisan ibge:importar-populacao-rj` — valida e grava/atualiza a população do Censo 2022 para os 92 municípios no banco local, com fonte e data de coleta.
- `php artisan ibge:verificar-cnaes` — confere os códigos e nomes de CNAE do IBGE; não grava dados. Requer acesso à API.
- `php artisan ibge:importar-cnaes` — valida as 1.332 subclasses CNAE recebidas do IBGE e grava/atualiza `atividades_economicas` no banco local; requer acesso à API.

## Preparação dos dados CNPJ

- `php artisan cnpj:importar-contagens-piloto "caminho/do/lote.json"` — novo fluxo dos quatro CNAEs de restaurantes/padarias/grupo de barbearias. Valida 1 a 368 grupos, mostra resumo por CNAE/hash e simula sem gravação. Pares ausentes não viram zero; padarias ficam separadas, e 9602501 não isola barbearias. Não acessa API/BigQuery. Ver [procedimento dos perfis](./procedimentos/contagens-perfis-piloto.md).
- `php artisan cnpj:importar-contagens-piloto "caminho/do/lote.json" --gravar` — exige fotografia conferida em `config/contagens_piloto.php` (hash LF, instante UTC e referências explícitas/NULL). Registro começa vazio. Transação preserva histórico/reimportação idêntica e rejeita divergência; fonte e coletas são desfeitas em caso de falha. Nenhuma nova carga foi executada nesta implementação.
- `php artisan test --compact` — na ampliação de 03/11/2026, suíte completa com **126 testes/701 asserções** aprovada; inclui 39 casos novos do importador piloto e dois do SQL em memória. Não é evidência de escrita/concorrência MySQL do novo comando ou execução GoogleSQL real.

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
