# Comandos do RightPoint

Execute na pasta `Right_Point/`.

## Aplicação

- `php artisan serve` — inicia a interface local.
- `php artisan migrate:status` — mostra quais migrations foram aplicadas neste banco.
- `php artisan test` — executa os testes automatizados.

## Dados do IBGE

- `php artisan ibge:consultar-rio` — consulta município e população do Rio de Janeiro; não grava dados.
- `php artisan ibge:importar-municipios-rj` — valida e grava/atualiza os 92 municípios do RJ no banco local.
- `php artisan ibge:verificar-populacao-rj` — confere a população de 2022 dos 92 municípios; não grava dados.
- `php artisan ibge:verificar-cnaes` — confere os códigos e nomes de CNAE do IBGE; não grava dados. Requer acesso à API.
