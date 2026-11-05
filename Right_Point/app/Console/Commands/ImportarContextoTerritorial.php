<?php

namespace App\Console\Commands;

use App\Models\ContextoTerritorial;
use App\Services\ValidadorContextoTerritorial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportarContextoTerritorial extends Command
{
    protected $signature = 'contexto:importar {arquivo : JSON normalizado conforme procedimento} {--gravar}';

    protected $description = 'Valida OSM, emprego, setores ou estabelecimentos geocodificados; não lê microdados brutos';

    public function handle(ValidadorContextoTerritorial $validador): int
    {
        try {
            $arquivo = $this->argument('arquivo');
            if (! is_file($arquivo) || ! is_readable($arquivo) || filesize($arquivo) > 10 * 1024 * 1024) {
                throw new \DomainException('Arquivo ausente, ilegível ou maior que 10 MiB.');
            }
            $dados = $validador->validar(json_decode(file_get_contents($arquivo), true, 512, JSON_THROW_ON_ERROR));
            $dados['sha256'] = hash('sha256', json_encode($dados, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $this->info('Contexto válido: '.$dados['tipo'].' / '.$dados['codigo_ibge'].' / SHA-256 '.$dados['sha256']);
            $this->warn('URL e hash identificam o lote; não certificam autenticidade ou cobertura.');
            if ($this->option('gravar')) {
                ContextoTerritorial::firstOrCreate(['sha256' => $dados['sha256']], $dados);
                $this->info('Lote preservado no banco; reimportar o mesmo conteúdo não duplica.');
            } else {
                $this->line('Simulação: nenhum dado gravado.');
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::warning('Contexto territorial rejeitado.', ['tipo_erro' => $e::class]);
            $this->error($e instanceof \DomainException ? $e->getMessage() : 'Falha ao ler, validar ou gravar contexto; confira o contrato e o log.');

            return self::FAILURE;
        }
    }
}
