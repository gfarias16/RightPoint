# Comandos do RightPoint

Execute na pasta `Right_Point/`.

As consultas ao IBGE usam automaticamente `storage/app/ibge-ca-bundle.pem` quando esse pacote CA local existe. Se ele não existir, usam a verificação TLS padrão do PHP. Veja o [procedimento de HTTPS](./procedimentos/ambiente-multiplos-computadores.md#acesso-https-ao-ibge-com-cadeia-incompleta) se ocorrer erro de certificado; não é necessário acrescentar opções `php -d` a cada comando.

## Aplicação

- `php artisan serve` — inicia a interface local.
- `php artisan migrate:status` — mostra quais migrations foram aplicadas neste banco.
- `php artisan test` — executa os testes automatizados.
- `GET /cnaes?busca=4929902` — busca até 20 CNAEs por código ou trecho do nome no banco local; o formulário já usa essa rota.

## Dados do IBGE

- `php artisan ibge:consultar-rio` — consulta município e população do Rio de Janeiro; não grava dados.
- `php artisan ibge:importar-municipios-rj` — valida e grava/atualiza os 92 municípios do RJ no banco local.
- `php artisan ibge:verificar-populacao-rj` — confere a população de 2022 dos 92 municípios; não grava dados.
- `php artisan ibge:importar-populacao-rj` — valida e grava/atualiza a população do Censo 2022 para os 92 municípios no banco local, com fonte e data de coleta.
- `php artisan ibge:verificar-cnaes` — confere os códigos e nomes de CNAE do IBGE; não grava dados. Requer acesso à API.
- `php artisan ibge:importar-cnaes` — valida as 1.332 subclasses CNAE recebidas do IBGE e grava/atualiza `atividades_economicas` no banco local; requer acesso à API.

## Preparação dos dados CNPJ

- `php artisan cnpj:verificar-estabelecimentos "caminho/do/arquivo"` — confere arquivo existente/legível, lê uma linha CSV separada por `;`, fecha o arquivo e mostra a quantidade de campos. Caminho inválido ou primeira linha vazia retorna falha. Ainda não valida o leiaute da Receita nem grava no banco. Para a amostra técnica movida pelo usuário, o caminho é `storage/teste-leitura.csv`, relativo a `Right_Point/`; a execução retornou 2 campos.
