<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class VerificarMunicipioReceita extends Command
{
    protected $signature = 'cnpj:verificar-municipio {codigo_receita} {uf=RJ}';

    protected $description = 'Relaciona um codigo municipal da Receita ao municipio local, sem gravar dados.';

    public function handle(): int
    {
        $codigoReceita = (string) $this->argument('codigo_receita');
        $uf = strtoupper((string) $this->argument('uf'));

        if (preg_match('/^[0-9]{4}$/D', $codigoReceita) !== 1 || preg_match('/^[A-Z]{2}$/D', $uf) !== 1) {
            $this->error('Informe o codigo Receita com quatro digitos e a UF com duas letras.');

            return self::FAILURE;
        }

        // Os dois codigos identificam o mesmo lugar, mas nao sao intercambiaveis.
        $codigoIbge = config('municipios_receita.'.$uf.':'.$codigoReceita);

        if (! is_string($codigoIbge) || preg_match('/^[0-9]{7}$/D', $codigoIbge) !== 1) {
            $this->error('Nao existe correspondencia conferida para esse codigo Receita e UF.');

            return self::FAILURE;
        }

        try {
            // Consulta pela referencia IBGE, nunca por um ID interno fixo.
            $municipio = DB::table('municipios')
                ->where('codigo_ibge', $codigoIbge)
                ->where('uf', $uf)
                ->first(['id', 'nome', 'codigo_ibge', 'uf']);
        } catch (QueryException $erro) {
            $this->error('Nao foi possivel consultar municipios. Confira a conexao MySQL e as migrations.');

            return self::FAILURE;
        }

        if ($municipio === null) {
            $this->error('O municipio correspondente ainda nao esta cadastrado neste banco.');

            return self::FAILURE;
        }

        $this->info('Municipio relacionado: '.$municipio->nome.'/'.$municipio->uf);
        $this->line('Codigo Receita: '.$codigoReceita);
        $this->line('Codigo IBGE: '.$municipio->codigo_ibge);
        $this->line('ID neste banco: '.$municipio->id);
        $this->line('Nenhum dado foi gravado. Correspondencia somente deste municipio.');

        return self::SUCCESS;
    }
}
