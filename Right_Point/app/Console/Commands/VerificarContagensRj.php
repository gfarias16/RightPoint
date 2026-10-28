<?php

namespace App\Console\Commands;

use App\Services\ValidadorContagensRj;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use JsonException;

class VerificarContagensRj extends Command
{
    protected $signature = 'cnpj:verificar-contagens-rj {arquivo : Caminho do JSON agregado local}';

    protected $description = 'Valida o lote de 92 municípios do RJ para o CNAE 5611201, sem gravar dados.';

    public function handle(ValidadorContagensRj $validador): int
    {
        try {
            $lote = $validador->validar($this->argument('arquivo'));
        } catch (JsonException) {
            $this->error('O arquivo não contém um JSON válido.');

            return self::FAILURE;
        } catch (DomainException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        } catch (QueryException) {
            $this->error('Não foi possível consultar os catálogos locais. Confira banco e migrations.');

            return self::FAILURE;
        }

        $linhas = array_map(fn ($linha) => [
            $linha['codigo_receita'], $linha['codigo_ibge'], $linha['nome'], $linha['quantidade'],
        ], $lote['linhas']);

        $this->table(['Código Receita', 'Código IBGE', 'Município', 'Estabelecimentos na base'], $linhas);
        $this->info('Registros conferidos: '.count($linhas));
        $this->line('Soma das contagens municipais: '.$lote['total']);
        $this->line('CNAE principal: 5611201 | Situação: Ativa | UF: RJ');
        $this->line('SHA-256 (LF): '.$lote['sha256']);
        $this->warn('A soma não comprova cobertura real ou unicidade de CNPJ entre municípios; zero não comprova ausência real.');
        $this->warn('Esta verificação não autentica o arquivo nem confirma edição/período da fonte.');
        $this->info('Simulação concluída. Nenhum dado foi gravado.');

        return self::SUCCESS;
    }
}
