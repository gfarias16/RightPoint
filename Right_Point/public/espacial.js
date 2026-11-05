(() => {
    'use strict';
    const config = window.rightpointDemo, map = window.rightpointMap;
    const status = document.getElementById('camadas-status');
    const points = document.getElementById('camada-pontos'), sectors = document.getElementById('camada-setores'), grid = document.getElementById('camada-h3');
    const resolution = document.getElementById('h3-resolucao');
    let context = null, codigo = '', cnae = '', version = 0, historic = false;
    const cache = new Map();
    function clear() { ['pontos', 'setores', 'h3'].forEach(key => map.draw(key, [])); }
    function draw() {
        clear();
        if (historic) { status.textContent = 'Fotografia histórica aberta. Camadas atuais ocultas; faça uma nova consulta para atualizar.'; return; }
        if (!codigo) { status.textContent = 'Selecione um município para usar as camadas.'; return; }
        const layers = context?.camadas || [];
        const imported = layers.flatMap(l => (l.dados.pontos || []).filter(p => l.tipo !== 'estabelecimentos' || p.cnae === cnae).map(p => ({ ...p, origem: l.tipo })));
        if (points.checked) map.draw('pontos', imported.map(p => ({ type: 'Feature', geometry: { type: 'Point', coordinates: [p.longitude, p.latitude] }, properties: { descricao: p.tipo + ' · ' + p.origem + ' · ponto representativo do lote' } })));
        if (sectors.checked) map.draw('setores', layers.filter(l => l.tipo === 'setores-ibge').flatMap(l => l.dados.features.map(f => ({ ...f, properties: { ...f.properties, descricao: f.properties.codigo_setor + ' · população: ' + (f.properties.populacao ?? 'suprimida/indisponível') } }))), { color: '#9eacff', fillOpacity: .04 });
        let message = imported.length + ' pontos no recorte importado. Ausência de camada não significa ausência real de equipamentos.';
        if (grid.checked) {
            const geometry = map.geometry();
            if (!window.h3 || !geometry) { status.textContent = 'A grade depende da biblioteca H3 e do limite municipal. Confira a rede e selecione novamente.'; return; }
            const res = Number(resolution.value);
            // Estima o limite antes de expandir: nunca gerar uma grade estadual densa no navegador.
            const polygons = geometry.type === 'Polygon' ? [geometry.coordinates] : geometry.coordinates;
            try {
                const base = [...new Set(polygons.flatMap(p => h3.polygonToCells(p, 6, true)))];
                const estimated = base.length * (7 ** (res - 6));
                if (estimated > 3000) { status.textContent = 'Este município excede o limite de 3.000 células nesta resolução. Escolha células maiores.'; return; }
                const cells = [...new Set(polygons.flatMap(p => h3.polygonToCells(p, res, true)))];
                if (cells.length > 3000) throw new Error('Grade excessiva');
                const counts = new Map();
                imported.forEach(p => { const id = h3.latLngToCell(p.latitude, p.longitude, res); counts.set(id, (counts.get(id) || 0) + 1); });
                map.draw('h3', cells.map(id => ({ type: 'Feature', geometry: { type: 'Polygon', coordinates: [h3.cellToBoundary(id, true)] }, properties: { descricao: id + ' · ' + (counts.get(id) || 0) + ' pontos do lote · população por célula não calculada' } })), { color: '#8be3c4', weight: .8 });
                const outside = imported.length - [...counts.entries()].filter(([id]) => cells.includes(id)).reduce((sum, [, n]) => sum + n, 0);
                message += ' ' + cells.length + ' células H3, resolução ' + res + '. Inclusão por centro; bordas podem ultrapassar o município e deixar pontos fora da grade (' + outside + '). Sem população ou score por célula.';
            } catch (_error) { message = 'Não foi possível gerar a grade neste recorte. Use uma resolução menor.'; }
        }
        status.textContent = message;
    }
    async function load() {
        const ownVersion = ++version;
        if (!codigo || historic) { draw(); return; }
        if (cache.has(codigo)) { context = cache.get(codigo); draw(); return; }
        if (!points.checked && !sectors.checked && !grid.checked) { draw(); return; }
        status.textContent = 'Carregando contextos já importados neste servidor…';
        try {
            const response = await fetch(config.contextoUrl + '/' + codigo, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (ownVersion !== version) return;
            context = data; cache.set(codigo, data); draw();
        } catch (_error) { if (ownVersion === version) status.textContent = 'Contextos indisponíveis. O mapa e a consulta principal continuam utilizáveis.'; }
    }
    document.addEventListener('rightpoint:selection', event => {
        codigo = event.detail.codigo_ibge; cnae = event.detail.cnae;
        context = null; ++version; clear(); load();
    });
    document.addEventListener('rightpoint:result', event => {
        historic = false;
        if (event.detail.data.contexto?.geometrias_incluidas) {
            context = event.detail.data.contexto; cache.set(codigo, context); draw();
        } else load();
    });
    document.addEventListener('rightpoint:snapshot', () => { historic = true; ++version; draw(); });
    document.addEventListener('rightpoint:mapready', draw);
    [points, sectors, grid, resolution].forEach(input => input.addEventListener('change', load));
})();
