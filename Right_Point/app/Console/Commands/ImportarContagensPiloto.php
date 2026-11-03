<?php

namespace App\Console\Commands;

use App\Services\GravadorContagensCnpj;
use App\Services\ValidadorContagensRj;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

class ImportarContagensPiloto extends Command
{
    protected $signature = 'cnpj:importar-contagens-piloto {arquivo : JSON agregado dos CNAEs piloto} {--gravar : Grava somente uma fotografia conferida}';

    protected $description = 'Valida e simula contagens RJ de restaurantes, padarias e serviços do grupo de barbearias.';

    public function handle(ValidadorContagensRj $validador, GravadorContagensCnpj $gravador): int
    {
        try {
            $lote = $validador->validarPiloto($this->argument('arquivo'));
            $metadados = $this->metadadosConferidos($lote['sha256']);
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

        $this->line('Grupos preparados: '.count($lote['linhas']));
        $this->line('SHA-256 (LF): '.$lote['sha256']);
        $this->table(['CNAE principal', 'Municípios com registro / 92', 'Soma na base'], array_map(
            fn ($resumo) => [$resumo['codigo_cnae'], $resumo['municipios_com_registro'].' / 92', $resumo['total'] ?? 'Indisponível'],
            $lote['resumo_cnaes']
        ));
        $this->warn('Pares município/CNAE ausentes ficam indisponíveis; não serão preenchidos com zero.');
        $this->warn('Padarias ficam separadas por CNAE. 9602501 inclui cabeleireiros, manicure e pedicure; não isola barbearias.');
        $this->warn('A contagem da fonte não comprova cobertura real ou chance de sucesso. Nenhum score é calculado.');

        if ($metadados === null) {
            $this->warn('Fotografia ainda não conferida em contagens_piloto.coletas. Gravação bloqueada.');
        } else {
            $this->line('Coleta (UTC): '.$metadados['consultado_em_utc']);
            $this->line('Período: '.($metadados['periodo_referencia'] ?? 'Não confirmado').' | Versão: '.($metadados['versao_fonte'] ?? 'Não confirmada'));
        }

        if (! $this->option('gravar')) {
            $this->info('Simulação concluída. Nenhum dado foi gravado.');

            return self::SUCCESS;
        }

        if ($metadados === null) {
            $this->error('Confira o arquivo e os metadados do job antes de cadastrar esta fotografia para gravação.');

            return self::FAILURE;
        }

        try {
            $resultado = $gravador->gravar(
                $lote['linhas'],
                $metadados['consultado_em_utc'],
                $metadados['periodo_referencia'],
                $metadados['versao_fonte']
            );
        } catch (DomainException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        } catch (Throwable $erro) {
            Log::error('Falha na importação das contagens piloto; transação desfeita.', [
                'operacao' => 'cnpj:importar-contagens-piloto',
                'tipo_erro' => $erro::class,
            ]);
            $this->error('Falha ao gravar as contagens piloto. A transação foi desfeita; confira o log local.');

            return self::FAILURE;
        }

        $this->info($resultado['criadas'] === 0
            ? 'Lote já cadastrado. Nenhuma alteração realizada.'
            : 'Importação concluída: '.$resultado['criadas'].' novas coletas; '.$resultado['existentes'].' já cadastradas.');

        return self::SUCCESS;
    }

    private function metadadosConferidos(string $hash): ?array
    {
        $coletas = config('contagens_piloto.coletas');

        if (! is_array($coletas)) {
            throw new DomainException('Registro de fotografias piloto inválido. Confira a configuração e seu cache.');
        }

        if (! array_key_exists($hash, $coletas)) {
            return null;
        }

        $dados = $coletas[$hash];
        $campos = ['consultado_em_utc', 'periodo_referencia', 'versao_fonte'];

        if (! is_array($dados) || array_diff($campos, array_keys($dados)) || array_diff(array_keys($dados), $campos)) {
            throw new DomainException('Metadados da fotografia piloto incompletos ou fora do contrato.');
        }

        $instante = $dados['consultado_em_utc'];
        $data = is_string($instante) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/D', $instante) === 1
            ? DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $instante, new DateTimeZone('UTC'))
            : false;

        if ($data === false || $data->format('Y-m-d H:i:s') !== $instante) {
            throw new DomainException('Instante UTC da fotografia piloto inválido. Não será usado o horário da importação.');
        }

        foreach (['periodo_referencia' => 20, 'versao_fonte' => 100] as $campo => $limite) {
            $valor = $dados[$campo];

            if ($valor !== null && (! is_string($valor) || trim($valor) === '' || mb_strlen($valor) > $limite
                || preg_match('/[\x00-\x1F\x7F]/u', $valor) === 1)) {
                throw new DomainException('Metadado '.$campo.' inválido; use referência conferida ou NULL.');
            }
        }

        return $dados;
    }
}
