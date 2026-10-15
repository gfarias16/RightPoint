<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class IbgeHttp
{
    public static function request(): PendingRequest
    {
        $caBundle = storage_path('app/ibge-ca-bundle.pem');

        // O pacote local corrige a cadeia incompleta do IBGE sem afetar outras integrações.
        return Http::withOptions(
            is_file($caBundle) ? ['verify' => $caBundle] : []
        );
    }
}
