<?php

return [
    // Chave: SHA-256 do JSON com quebras normalizadas para LF.
    // Registrar somente após conferir arquivo, SQL/job, instante UTC e procedência.
    // Cada entrada exige consultado_em_utc, periodo_referencia e versao_fonte.
    // Referências desconhecidas usam null; não reutilizar o instante da carga antiga.
    'coletas' => [
        // Job conferido por captura: 03/11/2026 17:41:41 UTC-3.
        'c6f18525e52c489384b8feaa9c6e700ccdf60acef3ee1b682facece2db6ce3b1' => [
            'consultado_em_utc' => '2026-11-03 20:41:41',
            'periodo_referencia' => null,
            'versao_fonte' => null,
        ],
    ],
];
