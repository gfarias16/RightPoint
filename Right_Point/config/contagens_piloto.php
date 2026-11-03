<?php

return [
    // Chave: SHA-256 do JSON com quebras normalizadas para LF.
    // Registrar somente após conferir arquivo, SQL/job, instante UTC e procedência.
    // Cada entrada exige consultado_em_utc, periodo_referencia e versao_fonte.
    // Referências desconhecidas usam null; não reutilizar o instante da carga antiga.
    'coletas' => [],
];
