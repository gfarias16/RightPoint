(() => {
    'use strict';

    const config = window.rightpointDemo;
    const municipalities = Array.isArray(config?.municipios) ? config.municipios : [];
    const byCode = new Map(municipalities.map((item) => [String(item.codigo_ibge), item]));
    const search = document.getElementById('municipio-busca');
    const select = document.getElementById('municipio-select');
    const cnae = document.getElementById('cnae');
    const currentCity = document.getElementById('municipio-atual');
    const mapStatus = document.getElementById('mapa-estado');
    const result = document.getElementById('resultado-conteudo');
    const profileForm = document.getElementById('perfil-form');
    const profileName = document.getElementById('perfil-nome');
    const profileNotes = document.getElementById('perfil-notas');
    const profileError = document.getElementById('perfil-form-error');
    const storageNote = document.getElementById('storage-aviso');
    const storageKey = 'rightpoint.perfis-prototipo.v1';
    const rjBounds = [[-23.40, -45.20], [-20.75, -40.90]];
    let map = null;
    let boundaryLayer = null;
    let selectedPolygon = null;
    let activeTiles = null;
    let baseStyle = 'escuro';
    let profiles = [];
    let editingId = null;

    //funcao para normalizar strings, removendo acentos e convertendo para minusculo
    function normalize(value) {
        return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }
    //funcao para exibir mensagens de erro no formulario
    function showError(element, message) {
        element.textContent = message;
        element.hidden = !message;
    }
    //funcao para renderizar a lista de municipios filtrados pelo input de busca
    function renderMunicipalOptions() {
        const previous = select.value;
        const term = normalize(search.value.trim());
        const matching = municipalities.filter((item) => normalize(item.nome).includes(term));
        select.replaceChildren(new Option(matching.length ? 'Selecione um município' : 'Nenhum município encontrado', ''));
        matching.forEach((item) => select.add(new Option(item.nome, String(item.codigo_ibge))));
        if (matching.some((item) => String(item.codigo_ibge) === previous)) select.value = previous;
    }
    //funcao para definir o estilo do poligono do municipio selecionado
    function polygonStyle(isSelected = false) {
        if (isSelected) return { color: '#f4b846', weight: 3, fillColor: '#f4b846', fillOpacity: .27 };
        if (baseStyle === 'claro') return { color: '#186855', weight: 1.2, fillColor: '#179a75', fillOpacity: .035 };
        if (baseStyle === 'satelite') return { color: '#f5f5e9', weight: 1.2, fillColor: '#a9d9d0', fillOpacity: .07 };
        return { color: '#8acfc2', weight: 1.2, fillColor: '#67bba9', fillOpacity: .075 };
    }
    //funcao para destacar o municipio selecionado no mapa
    function highlightCity() {
        if (!boundaryLayer) return;
        if (selectedPolygon) selectedPolygon.setStyle(polygonStyle());
        selectedPolygon = null;
        boundaryLayer.eachLayer((layer) => {
            const code = String(layer.feature?.properties?.codarea ?? '');
            if (code === select.value) {
                selectedPolygon = layer;
                layer.setStyle(polygonStyle(true));
                layer.bringToFront();
            }
        });
    }
    //funcao para selecionar um municipio e dar zoom no mapa
    function selectCity(code, zoomToCity = true) {
        const city = byCode.get(String(code));
        if (!city) return;
        search.value = city.nome;
        renderMunicipalOptions();
        select.value = String(code);
        currentCity.textContent = city.nome + ' · código IBGE ' + code;
        highlightCity();
        if (zoomToCity && selectedPolygon && map) {
            map.fitBounds(selectedPolygon.getBounds(), { padding: [25, 25], maxZoom: 11 });
        }
    }
    //funcao para alterar o estilo do mapa (claro, escuro ou satélite)
    function setBaseStyle(style) {
        if (!map || !['escuro', 'claro', 'satelite'].includes(style)) return;
        baseStyle = style;
        if (activeTiles) map.removeLayer(activeTiles);
        const isSatellite = style === 'satelite';
        activeTiles = isSatellite
            ? L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Imagens: Esri, Maxar, Earthstar Geographics e comunidade GIS',
                maxZoom: 18,
            })
            : L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            });
        activeTiles.addTo(map);
        document.querySelector('.leaflet-tile-pane')?.classList.toggle('is-dark', style === 'escuro');
        document.querySelectorAll('[data-basemap]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.basemap === style));
        });
        boundaryLayer?.eachLayer((layer) => layer.setStyle(polygonStyle(layer === selectedPolygon)));
    }
    //funcao para inicializar o mapa e carregar a malha municipal do RJ
    async function initializeMap() {
        if (!window.L) {
            showError(mapStatus, 'O mapa não carregou. A busca por município continua disponível.');
            mapStatus.classList.add('is-error');
            return;
        }

        // zoom direto pelo scroll; os botões +/- continuam como alternativa.
        map = L.map('municipio-mapa', {
            scrollWheelZoom: true,
            wheelPxPerZoomLevel: 120,
            zoomControl: true,
        });
        map.fitBounds(rjBounds);
        // A largura do painel muda após o layout responsivo; Leaflet precisa recalcular os mosaicos.
        if ('ResizeObserver' in window) {
            new ResizeObserver(() => map.invalidateSize({ pan: false })).observe(document.getElementById('municipio-mapa'));
        }
        setBaseStyle('escuro');
        document.querySelectorAll('[data-basemap]').forEach((button) => {
            button.addEventListener('click', () => setBaseStyle(button.dataset.basemap));
        });
        document.getElementById('mapa-ver-rj').addEventListener('click', () => map.fitBounds(rjBounds));

        // Carrega a malha municipal do RJ e adiciona os polígonos ao mapa.
        try {
            const response = await fetch(config.malhaUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Malha indisponível');
            const geojson = await response.json();
            if (geojson.type !== 'FeatureCollection' || !Array.isArray(geojson.features)) throw new Error('Formato da malha inesperado');

            // O código IBGE liga o polígono da malha ao município já cadastrado no banco.
            boundaryLayer = L.geoJSON(geojson, {
                style: () => polygonStyle(),
                onEachFeature(feature, layer) {
                    const code = String(feature.properties?.codarea ?? '');
                    const city = byCode.get(code);
                    if (!city) return;
                    layer.bindTooltip(city.nome, { sticky: true });
                    layer.on('click', () => selectCity(code));
                },
            }).addTo(map);
            if (boundaryLayer.getLayers().length !== 92) throw new Error('Cobertura municipal incompleta');
            mapStatus.textContent = '92 limites municipais carregados · fonte: IBGE';
            highlightCity();
        } catch (_error) {
            if (boundaryLayer) map.removeLayer(boundaryLayer);
            boundaryLayer = null;
            mapStatus.textContent = 'Limites municipais indisponíveis. Use a busca e a lista para selecionar.';
            mapStatus.classList.add('is-error');
        }
    }
    //funcao para criar elementos HTML com tag, classe e conteudo
    function makeElement(tag, className, content) {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (content !== undefined) element.textContent = String(content);
        return element;
    }
    //funcao para criar um item de fato (label e valor) para exibir no resultado da consulta
    function makeFact(label, value) {
        const item = makeElement('div', 'result-fact');
        item.append(makeElement('span', '', label), makeElement('strong', '', value));
        return item;
    }
    function makeDetails(title, ...children) {
        const details = makeElement('details', 'result-details');
        const body = makeElement('div', 'result-details__body');
        body.append(...children);
        details.append(makeElement('summary', '', title), body);
        return details;
    }

    function renderResult(data) {
        const report = makeElement('div', 'result-report');
        const context = makeElement('div', 'result-card');
        const diagnosis = data.diagnostico;
        const hasScore = diagnosis?.estado === 'experimental' && typeof data.score === 'number'
            && Number.isFinite(data.score) && data.score >= 0 && data.score <= 100;
        const number = (value, digits = 0) => new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: digits, maximumFractionDigits: digits
        }).format(value);
        const collection = (value) => value ? String(value).slice(0, 10) : 'Data não informada';
        context.append(makeElement('p', 'result-card__eyebrow', 'CONTEXTO DO MUNICÍPIO'),
            makeElement('h3', '', data.municipio.nome),
            makeElement('p', '', data.atividade.nome || 'CNAE ' + data.atividade.codigo_cnae));
        const facts = makeElement('div', 'result-facts');
        const population = data.populacao?.valor == null ? NaN : Number(data.populacao.valor);
        facts.append(makeFact('População · ' + (data.populacao?.periodo_referencia || 'sem referência'),
            Number.isFinite(population) && population >= 0 ? number(population) + ' pessoas' : 'Indisponível'));
        const income = data.renda?.valor == null || String(data.renda.valor).trim() === '' ? NaN : Number(data.renda.valor);
        facts.append(makeFact('Renda por pessoa · média mensal · ' + (data.renda?.periodo_referencia || 'sem referência'),
            Number.isFinite(income) && income >= 0
                ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(income) : 'Indisponível'));
        const count = data.concorrentes?.quantidade;
        facts.append(makeFact('Estabelecimentos registrados · mesmo CNAE',
            Number.isInteger(count) && count >= 0 ? number(count) : 'Indisponível'));
        const offer = data.oferta_por_10_mil_habitantes;
        const validOffer = offer?.estado === 'calculado' && offer.base_populacional === 10000
            && typeof offer.valor === 'number' && Number.isFinite(offer.valor) && offer.valor >= 0;
        facts.append(makeFact('Oferta registrada · por 10 mil habitantes', validOffer ? number(offer.valor, 2) : 'Indisponível'));
        context.append(facts);
        if (!validOffer && offer?.motivo) context.append(makeElement('p', 'result-indicator-note', offer.motivo));
        if (data.atividade.codigo_cnae === '9602501') {
            context.append(makeElement('p', 'result-indicator-note', 'Inclui cabeleireiros, manicure e pedicure; não isola barbearias.'));
        } else if (['1091102', '4721102'].includes(data.atividade.codigo_cnae)) {
            context.append(makeElement('p', 'result-indicator-note', 'Produção própria e revenda de padarias são analisadas separadamente.'));
        }

        const score = makeElement('div', 'result-card' + (hasScore ? ' result-card--score' : ' result-card--warning'));
        score.append(makeElement('p', 'result-card__eyebrow', hasScore ? 'ÍNDICE DE CONTEXTO' : 'NOTA INDISPONÍVEL'));
        if (hasScore) {
            score.append(makeElement('h3', 'result-score', number(data.score, 1) + ' / 100'));
            score.append(makeElement('p', 'result-status', 'Em avaliação · não representa chance de sucesso.'));
            const factors = makeElement('div', 'result-facts');
            factors.append(makeFact('Renda relativa · peso 50%', number(diagnosis.fatores.renda.pontos, 1) + ' pontos'),
                makeFact('Menor oferta relativa · peso 50%', number(diagnosis.fatores.menor_oferta.pontos, 1) + ' pontos'));
            score.append(factors);
            score.append(makeElement('p', 'result-indicator-note', 'Comparação entre os 92 municípios do RJ para esta atividade.'));
        } else {
            score.append(makeElement('h3', '', 'Dados insuficientes para esta análise'));
            (Array.isArray(diagnosis?.motivos) ? diagnosis.motivos : ['Confira a disponibilidade dos indicadores.'])
                .forEach((reason) => score.append(makeElement('p', 'result-indicator-note', reason)));
        }
        score.append(makeElement('p', 'result-indicator-note',
            hasScore ? 'Referências: população e renda de 2022; CNPJ 2026 como referência adotada. Veja fontes e limites abaixo.'
                : 'Confira as referências e a disponibilidade dos dados nos detalhes abaixo.'));
        report.append(context, score);

        const method = makeElement('div', 'result-details__copy');
        method.append(makeElement('p', '', 'Nota = 50% dos pontos de renda relativa + 50% dos pontos de menor oferta registrada por habitante. Os fatores usam a posição percentual entre municípios no mesmo CNAE, com empates pela posição média.'));
        method.append(makeElement('p', '', 'Oferta por 10 mil habitantes = estabelecimentos registrados ÷ população × 10.000. A escala facilita a leitura; não é um limite de saturação.'));
        if (diagnosis) method.append(makeElement('p', '', 'Método: ' + diagnosis.metodo + '. Conjunto apto: '
            + diagnosis.conjunto.municipios_aptos + ' / ' + diagnosis.conjunto.municipios_esperados + ' municípios.'));
        report.append(makeDetails('Como a nota é calculada', method));

        const sources = makeElement('div', 'result-facts');
        sources.append(makeFact('Código IBGE', data.municipio.codigo_ibge), makeFact('CNAE consultado', data.atividade.codigo_cnae));
        if (data.populacao) sources.append(makeFact('População · fonte / coleta',
            (data.populacao.fonte || 'Não informada') + ' · ' + collection(data.populacao.coletado_em)));
        if (data.renda) sources.append(makeFact('Renda · fonte / coleta',
            (data.renda.fonte || 'Não informada') + ' · ' + collection(data.renda.coletado_em)),
            makeFact('Renda · tabela / variável', data.renda.versao_fonte || 'Recorte não informado'));
        if (data.concorrentes) {
            const date = data.concorrentes.consultado_em ? new Date(data.concorrentes.consultado_em) : null;
            sources.append(makeFact('Estabelecimentos · fonte', data.concorrentes.fonte || 'Não informada'),
                makeFact('Consulta à fonte · UTC', date && !Number.isNaN(date.getTime())
                    ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short', timeZone: 'UTC' }).format(date) : 'Data não informada'),
                makeFact('Competência / versão confirmadas pela fonte',
                    (data.concorrentes.periodo_referencia || 'Competência não confirmada') + ' · '
                    + (data.concorrentes.versao_fonte || 'Versão não confirmada')));
        }
        const dates = makeElement('p', '', 'Os dados têm referências diferentes. A população é do Censo 2022 e a renda é nominal de julho/2022. No método em avaliação, 2026 é a referência adotada para o lote CNPJ definido, sem confirmação da competência pela fonte. A data da consulta não confirma o período dos registros.');
        report.append(makeDetails('Fontes e referências dos dados', dates, sources));

        const limits = makeElement('div', 'result-details__copy');
        limits.append(makeElement('p', '', 'Maior renda e menor oferta são hipóteses de contexto, ainda em avaliação. Menor oferta também pode refletir menor demanda. A nota não considera gestão, preços, custos, turismo ou fluxo local de consumidores.'));
        if (data.renda) limits.append(makeElement('p', '', 'A renda é uma média domiciliar mensal por pessoa, da amostra do Censo. Exclui moradores classificados como pensionistas, empregados domésticos ou parentes de empregados domésticos. Não equivale a salário individual ou gasto com esta atividade.'));
        const warnings = makeElement('ul', 'result-warnings');
        (Array.isArray(data.avisos) ? data.avisos : []).forEach((message) => warnings.append(makeElement('li', '', message)));
        limits.append(warnings);
        report.append(makeDetails('Limites desta análise', limits));
        result.replaceChildren(report);
    }

    document.getElementById('consulta-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formError = document.getElementById('form-error');
        showError(formError, '');
        if (!byCode.has(select.value)) {
            showError(formError, 'Selecione um município do RJ.');
            select.focus();
            return;
        }
        if (!/^[0-9]{7}$/.test(cnae.value.trim())) {
            showError(formError, 'Informe um CNAE com exatamente 7 números, sem pontuação.');
            cnae.focus();
            return;
        }
        const button = document.getElementById('consultar-button');
        button.disabled = true;
        result.replaceChildren(makeElement('p', 'result-empty', 'Consultando os dados disponíveis neste banco…'));
        try {
            const url = new URL(config.consultaUrl, window.location.href);
            url.searchParams.set('codigo_ibge', select.value);
            url.searchParams.set('cnae', cnae.value.trim());
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('A consulta não pôde ser concluída.');
            renderResult(await response.json());
        } catch (_error) {
            result.replaceChildren(makeElement('p', 'field-error', 'Não foi possível consultar o banco agora. Confira a conexão e tente novamente.'));
        } finally {
            button.disabled = false;
        }
    });
    //funcao para salvar os perfis no localStorage do navegador
    function saveProfiles() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(profiles));
            return true;
        } catch (_error) {
            storageNote.textContent = 'O navegador não permitiu salvar os perfis neste dispositivo.';
            storageNote.hidden = false;
            return false;
        }
    }
    //funcao para carregar os perfis salvos no localStorage do navegador
    function loadProfiles() {
        try {
            const stored = JSON.parse(localStorage.getItem(storageKey) || '[]');
            profiles = Array.isArray(stored) ? stored.filter((item) => item && byCode.has(String(item.codigo_ibge)) && /^[0-9]{7}$/.test(String(item.cnae))) : [];
        } catch (_error) {
            profiles = [];
            storageNote.textContent = 'Os perfis salvos neste navegador não puderam ser lidos.';
            storageNote.hidden = false;
        }
    }
    //funcao para sair do modo de edicao de perfil
    function exitEdit() {
        editingId = null;
        profileForm.reset();
        document.getElementById('perfil-form-titulo').textContent = 'Salvar perfil';
        document.getElementById('perfil-salvar').textContent = 'Salvar perfil local';
        document.getElementById('perfil-cancelar').hidden = true;
        showError(profileError, '');
    }
    //funcao para renderizar a lista de perfis salvos no navegador
    function renderProfiles() {
        const list = document.getElementById('perfis-lista');
        document.getElementById('perfis-contagem').textContent = profiles.length + (profiles.length === 1 ? ' perfil' : ' perfis');
        if (!profiles.length) {
            list.replaceChildren(makeElement('p', 'profiles-empty', 'Nenhum perfil salvo ainda. Preencha a consulta e salve o primeiro.'));
            return;
        }
        list.replaceChildren();
        profiles.forEach((profile) => {
            const item = makeElement('article', 'profile-item');
            const info = makeElement('div', 'profile-item__info');
            info.append(makeElement('h4', '', profile.nome), makeElement('p', '', (byCode.get(String(profile.codigo_ibge))?.nome || 'Município desconhecido') + ' · CNAE ' + profile.cnae));
            if (profile.notas) info.append(makeElement('p', 'profile-item__notes', profile.notas));
            const actions = makeElement('div', 'profile-item__actions');
            const use = makeElement('button', 'profile-action', 'Usar');
            const edit = makeElement('button', 'profile-action', 'Editar');
            const remove = makeElement('button', 'profile-action profile-action--danger', 'Excluir');
            [use, edit, remove].forEach((button) => { button.type = 'button'; });
            use.addEventListener('click', () => {
                selectCity(profile.codigo_ibge);
                cnae.value = profile.cnae;
                document.getElementById('explorar').scrollIntoView();
            });
            edit.addEventListener('click', () => {
                editingId = profile.id;
                selectCity(profile.codigo_ibge);
                cnae.value = profile.cnae;
                profileName.value = profile.nome;
                profileNotes.value = profile.notas || '';
                document.getElementById('perfil-form-titulo').textContent = 'Editar perfil';
                document.getElementById('perfil-salvar').textContent = 'Atualizar perfil local';
                document.getElementById('perfil-cancelar').hidden = false;
                profileName.focus();
            });
            remove.addEventListener('click', () => {
                if (!window.confirm('Excluir este perfil deste navegador?')) return;
                const updated = profiles.filter((record) => record.id !== profile.id);
                const previous = profiles;
                profiles = updated;
                if (!saveProfiles()) profiles = previous;
                if (editingId === profile.id) exitEdit();
                renderProfiles();
            });
            actions.append(use, edit, remove);
            item.append(info, actions);
            list.append(item);
        });
    }

    profileForm.addEventListener('submit', (event) => {
        event.preventDefault();
        showError(profileError, '');
        const name = profileName.value.trim();
        if (!name || name.length > 80) {
            showError(profileError, 'Informe um nome de até 80 caracteres para o perfil.');
            profileName.focus();
            return;
        }
        if (!byCode.has(select.value) || !/^[0-9]{7}$/.test(cnae.value.trim())) {
            showError(profileError, 'Selecione um município e informe um CNAE de 7 números na consulta acima.');
            return;
        }
        const previous = profiles;
        const record = { id: editingId || (window.crypto?.randomUUID?.() || String(Date.now())), nome: name, codigo_ibge: select.value, cnae: cnae.value.trim(), notas: profileNotes.value.trim().slice(0, 240) };
        profiles = editingId ? profiles.map((item) => item.id === editingId ? record : item) : [record, ...profiles];
        if (!saveProfiles()) {
            profiles = previous;
            return;
        }
        exitEdit();
        renderProfiles();
    });

    document.getElementById('perfil-cancelar').addEventListener('click', exitEdit);

    const cnaeSearch = document.getElementById('cnae-busca');
    const cnaeStatus = document.getElementById('cnae-busca-status');
    const cnaeOptionsField = document.getElementById('cnae-opcoes-campo');
    const cnaeOptions = document.getElementById('cnae-opcoes');
    let cnaeSearchTimer = null;
    let activeCnaeRequest = null;
    let cnaeSearchVersion = 0;

    cnaeSearch.addEventListener('input', () => {
        clearTimeout(cnaeSearchTimer);
        activeCnaeRequest?.abort();

        const version = ++cnaeSearchVersion;
        const term = cnaeSearch.value.trim();

        cnaeOptions.replaceChildren(new Option('Selecione uma atividade', ''));
        cnaeOptionsField.hidden = true;
        cnae.value = '';

        if (term.length < 2) {
            cnaeStatus.textContent = 'Digite pelo menos 2 caracteres.';
            return;
        }

        cnaeStatus.textContent = 'Buscando atividades…';

        // Espera uma pausa na digitação para evitar uma requisição por tecla.
        cnaeSearchTimer = setTimeout(async () => {
            const request = new AbortController();
            activeCnaeRequest = request;

            try {
                const url = new URL(config.cnaeUrl, window.location.href);
                url.searchParams.set('busca', term);

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                if (!response.ok) throw new Error('Falha na busca');

                const activities = await response.json();
                if (version !== cnaeSearchVersion) return;
                if (!Array.isArray(activities)) throw new Error('Resposta inválida');

                if (activities.length === 0) {
                    cnaeStatus.textContent = 'Nenhuma atividade encontrada. Tente outro termo.';
                    return;
                }

                activities.forEach((activity) => {
                    cnaeOptions.add(new Option(
                        `${activity.codigo_cnae} — ${activity.nome}`,
                        String(activity.codigo_cnae)
                    ));
                });

                cnaeOptionsField.hidden = false;
                cnaeStatus.textContent = activities.length === 20
                    ? 'Mostrando até 20 atividades. Refine a busca se necessário.'
                    : `${activities.length} atividade(s) encontrada(s).`;
            } catch (error) {
                if (error.name === 'AbortError' || version !== cnaeSearchVersion) return;
                cnaeStatus.textContent = 'Não foi possível buscar. Tente novamente ou digite o código CNAE.';
            } finally {
                if (activeCnaeRequest === request) activeCnaeRequest = null;
            }
        }, 350);
    });

    cnaeOptions.addEventListener('change', () => {
        cnae.value = cnaeOptions.value;
    });

    cnae.addEventListener('input', () => {
        if (cnae.value.trim() !== cnaeOptions.value) cnaeOptions.value = '';
    });

    search.addEventListener('input', renderMunicipalOptions);
    select.addEventListener('change', () => {
        if (select.value) selectCity(select.value);
        else {
            currentCity.textContent = 'Nenhum município selecionado';
            highlightCity();
        }
    });
    loadProfiles();
    renderProfiles();
    initializeMap();
})();
