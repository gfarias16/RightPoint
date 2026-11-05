(() => {
    'use strict';
    const config = window.rightpointDemo;
    if (!config?.autenticado) return;
    const ui = window.rightpointUi;
    const form = document.getElementById('perfil-form');
    const name = document.getElementById('perfil-nome');
    const notes = document.getElementById('perfil-notas');
    const error = document.getElementById('perfil-form-error');
    const save = document.getElementById('diagnostico-salvar');
    const status = document.getElementById('diagnostico-status');
    let editing = null, lastQuery = null;
    let profilePage = 1, historyPage = 1;
    const cities = new Map(config.municipios.map(city => [String(city.codigo_ibge), city.nome]));

    function el(tag, text, className = '') {
        const node = document.createElement(tag);
        node.textContent = text;
        node.className = className;
        return node;
    }
    function button(text, action, danger = false) {
        const node = el('button', text, 'profile-action' + (danger ? ' profile-action--danger' : ''));
        node.type = 'button';
        node.addEventListener('click', async () => {
            node.disabled = true;
            try { await action(); } catch (err) { status.textContent = err.message; }
            finally { node.disabled = false; }
        });
        return node;
    }
    async function api(url, method = 'GET', data) {
        const response = await fetch(url, {
            method, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            ...(data === undefined ? {} : { body: JSON.stringify(data) }),
        });
        if (response.status === 204) return null;
        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = response.status === 401 || response.status === 419 ? 'Sua sessão expirou. Entre novamente na conta.'
                : response.status === 422 ? Object.values(body.errors || {}).flat().join(' ')
                : response.status === 429 ? 'Muitas solicitações. Aguarde um minuto e tente novamente.'
                : 'Não foi possível acessar seus registros. Confira a conexão e as migrations deste servidor.';
            throw new Error(message || 'Confira os campos informados.');
        }
        return body;
    }
    function resetEdit() {
        editing = null; form.reset(); error.hidden = true;
        document.getElementById('perfil-form-titulo').textContent = 'Salvar perfil';
        document.getElementById('perfil-salvar').textContent = 'Salvar perfil na conta';
        document.getElementById('perfil-cancelar').hidden = true;
    }
    function paginate(prefix, page, last, load) {
        const prev = document.getElementById(prefix + '-anterior');
        const next = document.getElementById(prefix + '-proximo');
        prev.disabled = page <= 1; next.disabled = page >= last;
        prev.onclick = () => load(page - 1); next.onclick = () => load(page + 1);
    }
    async function profiles(page = 1) {
        const list = document.getElementById('perfis-lista');
        try {
            const data = await api(config.perfisUrl + '?page=' + page);
            profilePage = data.current_page;
            list.replaceChildren();
            document.getElementById('perfis-contagem').textContent = data.total + ' perfis';
            if (!data.data.length) list.append(el('p', 'Nenhum perfil salvo nesta conta.', 'profiles-empty'));
            data.data.forEach(profile => {
                const item = el('article', '', 'profile-item'), info = el('div', ''), actions = el('div', '', 'profile-item__actions');
                info.append(el('h4', profile.nome), el('p', (cities.get(profile.codigo_ibge) || profile.codigo_ibge) + ' · CNAE ' + profile.cnae));
                if (profile.notas) info.append(el('p', profile.notas, 'profile-item__notes'));
                actions.append(button('Usar', () => { ui.select(profile); document.getElementById('explorar').scrollIntoView(); }),
                    button('Editar', () => {
                        editing = profile.id; ui.select(profile); name.value = profile.nome; notes.value = profile.notas || '';
                        document.getElementById('perfil-form-titulo').textContent = 'Editar perfil';
                        document.getElementById('perfil-salvar').textContent = 'Atualizar perfil na conta';
                        document.getElementById('perfil-cancelar').hidden = false; name.focus();
                    }), button('Excluir', async () => {
                        if (!confirm('Excluir este perfil da sua conta? Seus diagnósticos serão preservados.')) return;
                        await api(config.perfisUrl + '/' + profile.id, 'DELETE');
                        if (editing === profile.id) resetEdit();
                        await profiles(1);
                    }, true));
                item.append(info, actions); list.append(item);
            });
            paginate('perfis', data.current_page, data.last_page, profiles);
        } catch (err) { list.replaceChildren(el('p', err.message, 'field-error')); }
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const submit = document.getElementById('perfil-salvar'); submit.disabled = true; error.hidden = true;
        try {
            await api(config.perfisUrl + (editing ? '/' + editing : ''), editing ? 'PUT' : 'POST', {
                nome: name.value.trim(), notas: notes.value.trim(), codigo_ibge: document.getElementById('municipio-select').value, cnae: document.getElementById('cnae').value.trim(),
            });
            resetEdit(); await profiles(profilePage);
        } catch (err) { error.textContent = err.message; error.hidden = false; }
        finally { submit.disabled = false; }
    });
    document.getElementById('perfil-cancelar').addEventListener('click', resetEdit);

    document.getElementById('perfis-importar').addEventListener('click', async event => {
        const node = event.currentTarget; node.disabled = true;
        try {
            const stored = JSON.parse(localStorage.getItem('rightpoint.perfis-prototipo.v1') || '[]');
            if (!Array.isArray(stored) || stored.length > 100) throw new Error('A cópia aceita até 100 perfis locais por operação.');
            const valid = stored.filter(p => p && p.id && typeof p.nome === 'string' && cities.has(String(p.codigo_ibge)) && /^[0-9]{7}$/.test(String(p.cnae)));
            if (!valid.length) { status.textContent = 'Nenhum perfil local compatível encontrado.'; return; }
            if (!confirm('Copiar ' + valid.length + ' perfis para esta conta? Os originais do navegador serão preservados.')) return;
            let count = 0;
            for (const p of valid) {
                await api(config.perfisUrl, 'POST', { nome: p.nome, codigo_ibge: String(p.codigo_ibge), cnae: String(p.cnae), notas: p.notas || '', origem_local: String(p.id).slice(0, 80) });
                count++; status.textContent = count + ' perfis processados. Origens já copiadas não são duplicadas.';
            }
            await profiles(1);
        } catch (err) { status.textContent = 'Cópia interrompida: ' + err.message + ' Os originais locais continuam preservados.'; }
        finally { node.disabled = false; }
    });

    document.addEventListener('rightpoint:selection', () => { lastQuery = null; save.disabled = true; status.textContent = ''; });
    document.addEventListener('rightpoint:result', event => {
        lastQuery = { ...event.detail.query }; save.disabled = false;
        document.getElementById('diagnostico-titulo').value = (event.detail.data.municipio.nome + ' · ' + (event.detail.data.atividade.nome || event.detail.query.cnae)).slice(0, 120);
    });
    save.addEventListener('click', async () => {
        if (!lastQuery) return;
        const query = { ...lastQuery }; save.disabled = true;
        try {
            const data = await api(config.diagnosticosUrl, 'POST', { ...query, titulo: document.getElementById('diagnostico-titulo').value.trim() });
            status.replaceChildren(el('span', 'Diagnóstico salvo. O servidor recalculou e preservou os dados disponíveis no instante do salvamento. '));
            const link = el('a', 'Abrir relatório / PDF'); link.href = config.diagnosticosUrl + '/' + data.id + '/relatorio'; status.append(link);
            await history(1);
        } catch (err) { status.textContent = err.message; }
        finally { save.disabled = !lastQuery; }
    });
    async function history(page = 1) {
        const list = document.getElementById('historico-lista');
        try {
            const data = await api(config.diagnosticosUrl + '?page=' + page); historyPage = data.current_page;
            list.replaceChildren();
            if (!data.data.length) list.append(el('p', 'Nenhum diagnóstico salvo. Faça uma consulta e clique em Salvar diagnóstico.', 'profiles-empty'));
            data.data.forEach(record => {
                const item = el('article', '', 'profile-item'), info = el('div', ''), actions = el('div', '', 'profile-item__actions');
                const date = new Date(record.created_at);
                info.append(el('h4', record.titulo), el('p', record.cnae + ' · ' + (Number.isNaN(date.getTime()) ? 'Data não disponível' : new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(date))));
                actions.append(button('Reabrir', async () => {
                    const saved = await api(config.diagnosticosUrl + '/' + record.id);
                    ui.select(saved); lastQuery = null; save.disabled = true; ui.showSnapshot(saved.snapshot);
                    status.textContent = 'Você está vendo uma fotografia salva. Faça uma nova consulta para atualizar os dados.';
                }));
                const report = el('a', 'Relatório / PDF', 'profile-action'); report.href = config.diagnosticosUrl + '/' + record.id + '/relatorio'; actions.append(report);
                actions.append(button('Excluir', async () => {
                    if (!confirm('Excluir permanentemente este diagnóstico salvo?')) return;
                    await api(config.diagnosticosUrl + '/' + record.id, 'DELETE'); await history(1);
                }, true));
                item.append(info, actions); list.append(item);
            });
            paginate('historico', historyPage, data.last_page, history);
        } catch (err) { list.replaceChildren(el('p', err.message, 'field-error')); }
    }
    profiles(); history();
})();
