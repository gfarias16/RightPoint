<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b0f10">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="RightPoint: protótipo acadêmico de exploração territorial para negócios no Rio de Janeiro.">
    <title>Explorar municípios — RightPoint</title>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="preconnect" href="https://tile.openstreetmap.org" crossorigin>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link rel="stylesheet" href="{{ asset('rightpoint.css') }}?v={{ filemtime(public_path('rightpoint.css')) }}">
    <script>
        window.rightpointDemo = {{ \Illuminate\Support\Js::from([
            'municipios' => $municipios,
            'consultaUrl' => route('consulta.demonstrativa'),
            'malhaUrl' => route('malha.rj'),
            'cnaeUrl' => route('cnaes.buscar'),
            'autenticado' => auth()->check(),
            'perfisUrl' => route('perfis.index'),
            'diagnosticosUrl' => route('diagnosticos.index'),
            'loginUrl' => route('login'),
            'contextoUrl' => url('/contextos'),
        ]) }};
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
    <script src="https://unpkg.com/h3-js@4.3.0/dist/h3-js.umd.js" integrity="sha384-Bd6Gu52dTUBWMKFwlS9PTFn4VymwtB2vNIeCcAyTc/zXMo+vPGfPo08WTOHqveEN" crossorigin="anonymous" defer></script>
    <script src="{{ asset('rightpoint.js') }}?v={{ filemtime(public_path('rightpoint.js')) }}" defer></script>
    <script src="{{ asset('workspace.js') }}?v={{ filemtime(public_path('workspace.js')) }}" defer></script>
    <script src="{{ asset('espacial.js') }}?v={{ filemtime(public_path('espacial.js')) }}" defer></script>
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand" href="{{ route('explorar') }}" aria-label="RightPoint, página inicial">
                <span class="brand__mark" aria-hidden="true"><span></span></span>
                <span>RightPoint</span>
            </a>

            <nav class="site-nav" aria-label="Navegação principal">
                <a href="#explorar">Explorar</a>
                <a href="#resultado">Diagnóstico</a>
                <a href="#perfis">Meus perfis</a>
                <a href="#historico">Histórico</a>
                @auth
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="profile-action" type="submit">Sair · {{ auth()->user()->name }}</button></form>
                @else
                    <a href="{{ route('login') }}">Entrar</a>
                @endauth
            </nav>

        </div>
    </header>

    <main id="conteudo" class="page-shell">
        <section class="intro" aria-labelledby="titulo-pagina">
            <div>
                <h1 id="titulo-pagina">Um ponto de partida para o seu negócio.</h1>
                <p class="intro__text">
                    Escolha um município e uma atividade para explorar
                </p>
            </div>
            <!-- <div class="intro__aside" aria-label="Escopo da demonstração">
                <span class="intro__number">{{ $municipios->count() }}</span>
                <span>municípios do RJ<br>no catálogo local</span>
                <span class="intro__aside-note">Sem promessa de sucesso. Com transparência sobre os dados.</span>
            </div> -->
        </section>

        <section id="explorar" class="explorer-grid" aria-label="Exploração de município e atividade">
            <div class="panel selection-panel">
                <div class="consulta-heading"><div class="section-heading">
                    <span class="step-number">01</span>
                    <div>
                        <p class="section-heading__kicker">PREPARE A CONSULTA</p>
                        <h2>Prepare sua consulta</h2>
                    </div>
                </div><button id="consulta-recolher" type="button" class="profile-action" aria-expanded="true" aria-controls="consulta-fields">Recolher campos ↑</button></div>

                <form id="consulta-form" novalidate>
                    <div id="consulta-fields" class="consulta-fields"><div class="consulta-field-group">
                    <div class="field">
                        <label for="municipio-busca">Buscar município</label>
                        <input id="municipio-busca" name="busca_municipio" type="search" autocomplete="off" placeholder="Digite parte do nome…">
                        <small>Busque pelo nome ou selecione diretamente no mapa.</small>
                    </div>

                    <div class="field">
                        <label for="municipio-select">Município selecionado <span aria-hidden="true">*</span></label>
                        <select id="municipio-select" name="codigo_ibge" required>
                            <option value="">Selecione um município</option>
                            @foreach ($municipios as $municipio)
                                <option value="{{ $municipio->codigo_ibge }}">{{ $municipio->nome }}</option>
                            @endforeach
                        </select>
                        <small id="municipio-ajuda">A seleção usa o código oficial do IBGE.</small>
                    </div>
                    </div><div class="consulta-field-group">
                    <div class="field">
                        <label for="cnae-busca">Buscar atividade por nome ou código</label>
                        <input id="cnae-busca" name="busca_atividade" type="search" autocomplete="off" maxlength="80"
                            aria-describedby="cnae-busca-status" placeholder="Ex.: restaurantes">
                        <small id="cnae-busca-status" role="status" aria-live="polite">
                            Digite pelo menos 2 caracteres.
                        </small>
                    </div>

                    <div class="field" id="cnae-opcoes-campo" hidden>
                        <label for="cnae-opcoes">Atividades encontradas</label>
                        <select id="cnae-opcoes" name="atividade_encontrada">
                            <option value="">Selecione uma atividade</option>
                        </select>
                    </div>
                    </div>
                    <div class="field">
                        <label for="cnae">Código CNAE da atividade <span aria-hidden="true">*</span></label>
                        <input id="cnae" name="cnae" type="text" inputmode="numeric" autocomplete="off" spellcheck="false" pattern="[0-9]{7}" minlength="7" maxlength="7" placeholder="Ex.: 5611201" required>
                        <small>É a classificação da atividade do negócio.<a href="https://concla.ibge.gov.br/busca-online-cnae.html" target="_blank" rel="noopener noreferrer">Buscar CNAE no IBGE (nova aba)</a>.</small>
                    </div>
                    </div><div class="consulta-actions">
                    <p id="form-error" class="field-error" role="alert" hidden></p>
                    <button id="consultar-button" class="button button--primary" type="submit" disabled>Ver diagnóstico <span aria-hidden="true">→</span></button>
                    <small id="consulta-prontidao" role="status">Selecione município e atividade ou informe um CNAE válido.</small>
                    </div>
                </form>
                <div class="consulta-summary"><p id="consulta-resumo" role="status">Nenhum município ou atividade selecionados.</p><button id="consulta-limpar" type="button" class="profile-action">Limpar seleção</button></div>
                <p class="selection-panel__footnote">Compare municípios dentro da mesma atividade. O índice atual cobre quatro CNAEs; outras consultas podem mostrar dados parciais, sem nota.</p>
            </div>

            <div class="panel map-panel">
                <div class="map-panel__top">
                    <div class="section-heading">
                        <span class="step-number">02</span>
                        <div>
                            <p class="section-heading__kicker">EXPLORE O TERRITÓRIO</p>
                            <h2>Mapa dos municípios</h2>
                        </div>
                    </div>
                    <span class="map-panel__source">Malha simplificada · IBGE</span>
                </div>

                <div class="map-canvas">
                    <div id="municipio-mapa" role="group" aria-label="Mapa dos municípios do Rio de Janeiro"></div>
                    <div class="map-layers" role="group" aria-label="Aparência do mapa">
                        <button class="map-layers__button" type="button" data-basemap="escuro" aria-pressed="true">Escuro</button>
                        <button class="map-layers__button" type="button" data-basemap="claro" aria-pressed="false">Claro</button>
                        <button class="map-layers__button" type="button" data-basemap="satelite" aria-pressed="false">Satélite</button>
                    </div>
                    <button id="mapa-ver-rj" class="map-reset" type="button">Ver todo o RJ</button>
                </div>

                <div class="map-panel__bottom">
                    <p id="mapa-estado" class="map-status" role="status" aria-live="polite">Carregando limites municipais…</p>
                    <p id="municipio-atual" class="selected-city">Nenhum município selecionado</p>
                </div>
                <p class="map-panel__help">Clique em um município ou use a busca e a lista.</p>
                <details class="spatial-controls"><summary>Camadas de contexto e grade H3</summary>
                    <div class="button-row">
                        <label><input id="camada-pontos" type="checkbox"> Pontos importados</label>
                        <label><input id="camada-setores" type="checkbox"> Setores IBGE importados</label>
                        <label><input id="camada-h3" type="checkbox"> Grade H3</label>
                        <label for="h3-resolucao">Resolução H3</label><select id="h3-resolucao"><option value="6">6 · células maiores</option><option value="7" selected>7 · intermediária</option><option value="8">8 · células menores</option></select>
                    </div>
                    <p id="camadas-status" role="status">Selecione um município. H3 organiza pontos; não estima população por célula.</p>
                    <p>Pontos OSM: © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors · ODbL (nova aba)</a>. Confira a referência de cada camada nos detalhes do diagnóstico.</p>
                </details>
            </div>
        </section>

        <section id="resultado" class="result-section" aria-labelledby="resultado-titulo">
            <div class="section-heading">
                <span class="step-number">03</span>
                <div>
                    <p class="section-heading__kicker">LEITURA DOS DADOS</p>
                    <h2 id="resultado-titulo">Diagnóstico disponível</h2>
                </div>
            </div>

            <div id="resultado-conteudo" class="result-empty" aria-live="polite">
                <span class="result-empty__symbol" aria-hidden="true">↗</span>
                <div>
                    <h3>Seu diagnóstico começa com uma escolha.</h3>
                    <p>Selecione um município e informe um CNAE. Mostraremos os indicadores que já existem no banco e as limitações da análise.</p>
                </div>
            </div>
            <div class="result-save-bar print-hidden">
                @auth
                    <div class="field"><label for="diagnostico-titulo">Nome deste diagnóstico</label><input id="diagnostico-titulo" maxlength="120" placeholder="Ex.: Restaurante em Maricá"></div>
                    <button id="diagnostico-salvar" class="button button--secondary" type="button" disabled>Salvar diagnóstico</button>
                @else
                    <p><a href="{{ route('login') }}">Entre ou crie uma conta</a> para guardar e exportar diagnósticos privados.</p>
                @endauth
                <p id="diagnostico-status" role="status" aria-live="polite"></p>
            </div>
        </section>

        <section id="perfis" class="profiles-section" aria-labelledby="perfis-titulo">
            <div class="profiles-section__intro">
                <div class="section-heading">
                    <span class="step-number">04</span>
                    <div>
                        <p class="section-heading__kicker">ÁREA DE EXPERIMENTAÇÃO</p>
                        <h2 id="perfis-titulo">Perfis do negócio</h2>
                    </div>
                </div>
                <p>@auth Guarde combinações na sua conta neste servidor. Perfis antigos do navegador só serão copiados se você solicitar. @else Sem conta, seus perfis ficam neste navegador. Entre para usar perfis privados no banco e acessá-los em outros dispositivos ligados ao mesmo servidor. @endauth</p>
            </div>

            <div class="profiles-grid">
                <form id="perfil-form" class="panel profile-form" novalidate>
                    <h3 id="perfil-form-titulo">Salvar perfil</h3>
                    <p>Use o município e o CNAE selecionados acima.</p>
                    <div class="field">
                        <label for="perfil-nome">Nome do perfil <span aria-hidden="true">*</span></label>
                        <input id="perfil-nome" name="nome" type="text" autocomplete="off" maxlength="80" placeholder="Ex.: Minha futura cafeteria" required>
                    </div>
                    <div class="field">
                        <label for="perfil-notas">Observações <span class="optional">opcional</span></label>
                        <textarea id="perfil-notas" name="notas" rows="3" maxlength="240" placeholder="O que você quer observar nessa região…"></textarea>
                    </div>
                    <p id="perfil-form-error" class="field-error" role="alert" hidden></p>
                    <div class="button-row">
                        <button id="perfil-salvar" class="button button--secondary" type="submit">{{ auth()->check() ? 'Salvar perfil na conta' : 'Salvar perfil local' }}</button>
                        <button id="perfil-cancelar" class="button button--text" type="button" hidden>Cancelar edição</button>
                    </div>
                    <p id="storage-aviso" class="storage-note" role="status" hidden></p>
                </form>

                <div class="panel profiles-list-panel">
                    <div class="profiles-list-panel__heading">
                        <h3>{{ auth()->check() ? 'Perfis da sua conta' : 'Seus perfis neste navegador' }}</h3>
                        <span id="perfis-contagem" class="count-pill">0 perfis</span>
                    </div>
                    <div id="perfis-lista" class="profiles-list" aria-live="polite">
                        <p class="profiles-empty">Nenhum perfil salvo ainda. Preencha a consulta e salve o primeiro.</p>
                    </div>
                    @auth
                    <div class="button-row"><button id="perfis-anterior" type="button" class="profile-action" disabled>Anterior</button><button id="perfis-proximo" type="button" class="profile-action" disabled>Próxima</button><button id="perfis-importar" type="button" class="profile-action">Copiar perfis deste navegador</button></div>
                    @endauth
                </div>
            </div>
        </section>
        <section id="historico" class="profiles-section" aria-labelledby="historico-titulo">
            <div class="section-heading"><span class="step-number">05</span><div><p class="section-heading__kicker">RASTREABILIDADE</p><h2 id="historico-titulo">Seus diagnósticos salvos</h2></div></div>
            <div class="panel profiles-list-panel">
                <p>Cada registro guarda os dados, fontes e método daquele momento. Reabrir não recalcula o resultado.</p>
                <div id="historico-lista" aria-live="polite"><p class="profiles-empty">{{ auth()->check() ? 'Carregando seu histórico…' : 'Entre na sua conta para salvar e consultar seu histórico.' }}</p></div>
                @auth<div class="button-row"><button id="historico-anterior" type="button" class="profile-action" disabled>Anterior</button><button id="historico-proximo" type="button" class="profile-action" disabled>Próxima</button></div>@endauth
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="site-footer__inner">
            <span>RightPoint · Projeto acadêmico</span>
            <span>Fontes e referências acompanham cada análise. Índice experimental, não probabilidade de sucesso.</span>
        </div>
    </footer>
</body>
</html>
