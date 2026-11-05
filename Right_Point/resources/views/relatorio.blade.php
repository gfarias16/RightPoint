<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $registro->titulo }} — RightPoint</title>
    <link rel="stylesheet" href="{{ asset('rightpoint.css') }}?v={{ filemtime(public_path('rightpoint.css')) }}">
    <script src="{{ asset('relatorio.js') }}" defer></script>
</head>
<body>
<main class="page-shell report-shell">
    <nav class="button-row print-hidden" aria-label="Ações do relatório"><a href="{{ route('explorar') }}" class="button button--text">Voltar</a><button type="button" id="imprimir" class="button button--secondary">Imprimir / salvar PDF</button><a href="{{ route('diagnosticos.index') }}/{{ $registro->id }}/json" class="button button--text">Exportar dados JSON</a></nav>
    <p class="eyebrow">RIGHTPOINT · DIAGNÓSTICO SALVO</p>
    <h1>{{ $registro->titulo }}</h1>
    <p>{{ $dados['municipio']['nome'] }} · CNAE {{ $dados['atividade']['codigo_cnae'] }} · {{ $dados['atividade']['nome'] ?? 'Atividade não catalogada' }}</p>
    <p>Gerado em {{ $dados['gerado_em_utc'] }}. Esta é a fotografia salva: os indicadores não são recalculados ao reabrir.</p>
    <h2>Índice de contexto comercial: {{ $dados['score'] === null ? 'indisponível' : number_format($dados['score'], 1, ',', '.').' / 100' }}</h2>
    <p>Índice experimental, relativo ao mesmo CNAE entre municípios. Não representa chance de sucesso ou recomendação de investimento.</p>
    <div class="result-facts">
        <div class="result-fact"><span>População · {{ $dados['populacao']['periodo_referencia'] ?? 'sem referência' }}</span><strong>{{ isset($dados['populacao']['valor']) ? number_format($dados['populacao']['valor'], 0, ',', '.') : 'Indisponível' }}</strong></div>
        <div class="result-fact"><span>Renda mensal média por pessoa · {{ $dados['renda']['periodo_referencia'] ?? 'sem referência' }}</span><strong>{{ isset($dados['renda']['valor']) ? 'R$ '.number_format($dados['renda']['valor'], 2, ',', '.') : 'Indisponível' }}</strong></div>
        <div class="result-fact"><span>Estabelecimentos registrados</span><strong>{{ $dados['concorrentes']['quantidade'] ?? 'Indisponível' }}</strong></div>
        <div class="result-fact"><span>Estabelecimentos por 10 mil habitantes</span><strong>{{ isset($dados['oferta_por_10_mil_habitantes']['valor']) ? number_format($dados['oferta_por_10_mil_habitantes']['valor'], 2, ',', '.') : 'Indisponível' }}</strong></div>
    </div>
    <h2>Como chegamos ao resultado</h2>
    <p>Média dos pontos de renda relativa e menor quantidade registrada por habitante, com peso 50% em cada componente. Posições relativas não medem demanda real ou saturação.</p>
    @if ($dados['diagnostico']['fatores'])
        <table><thead><tr><th>Componente</th><th>Pontos</th><th>Peso</th><th>Contribuição</th></tr></thead><tbody>
        @foreach ($dados['diagnostico']['fatores'] as $nome => $fator)<tr><td>{{ $nome === 'renda' ? 'Renda relativa' : 'Menor oferta registrada relativa' }}</td><td>{{ number_format($fator['pontos'], 4, ',', '.') }}</td><td>{{ $fator['peso'] * 100 }}%</td><td>{{ number_format($fator['contribuicao'], 4, ',', '.') }}</td></tr>@endforeach
        </tbody></table>
    @endif
    <h2>Fontes e referências</h2>
    @foreach (['populacao' => 'População', 'renda' => 'Renda', 'concorrentes' => 'Estabelecimentos'] as $chave => $rotulo)
        @if ($dados[$chave])<p><strong>{{ $rotulo }}:</strong> {{ $dados[$chave]['fonte'] ?? 'Fonte não informada' }} · referência {{ $dados[$chave]['periodo_referencia'] ?? 'não confirmada' }} · versão {{ $dados[$chave]['versao_fonte'] ?? 'não confirmada' }} · consulta {{ $dados[$chave]['coletado_em'] ?? $dados[$chave]['consultado_em'] ?? 'não informada' }}.</p>@endif
    @endforeach
    <p>O método assume 2026 para a fotografia CNPJ definida no projeto; a fonte não confirmou sua competência. População e renda de 2022 não representam uma fotografia integral de 2026.</p>
    <h2>Limitações e dados insuficientes</h2>
    <ul>@foreach (array_merge($dados['avisos'], $dados['diagnostico']['motivos']) as $aviso)<li>{{ $aviso }}</li>@endforeach</ul>
    <p>Custos, preços, gestão, fluxo de consumidores e demanda efetiva não estão medidos. Menos estabelecimentos pode indicar pouca procura; renda média não é gasto comercial.</p>
    @if (!empty($dados['contexto']))
        <h2>Contextos complementares — fora do score</h2>
        @foreach ($dados['contexto']['indicadores'] as $i)<p>{{ $i['codigo'] }}: {{ number_format($i['valor'], 0, ',', '.') }} {{ $i['unidade'] }} · {{ $i['periodo_referencia'] }} · {{ $i['fonte'] }} · consulta {{ $i['coletado_em'] }}.</p>@endforeach
        @foreach ($dados['contexto']['camadas'] as $c)<p>{{ $c['tipo'] }} · {{ $c['fonte_url'] }} · referência {{ $c['periodo_referencia'] ?? 'não confirmada' }} · consulta {{ $c['coletado_em'] }}. {{ $c['dados']['aviso'] }}</p>@endforeach
        <p>{{ $dados['contexto']['aviso'] }}</p>
    @endif
    <h2>Rastreabilidade</h2>
    <p>Método: {{ $registro->metodo }}. Entradas comparadas preservadas: {{ count($dados['diagnostico']['entradas_comparacao'] ?? []) }} municípios.</p>
    <p class="report-hash">Identificador: {{ $registro->id }}<br>SHA-256: {{ $registro->sha256 }}</p>
    <p>O JSON contém os dados da fotografia, inclusive as entradas da comparação quando o conjunto estava apto. O hash identifica o conteúdo, não certifica a fonte ou a eficácia comercial.</p>
</main>
</body>
</html>
