import { parseContactFile } from './messaging-import';

const root = document.querySelector('[data-messaging-module]');
if (root) {
    const $ = (selector) => root.querySelector(selector);
    const make = (tag, text) => { const node = document.createElement(tag); if (text !== undefined) node.textContent = text; return node; };
    async function request(url, data) {
        let response;
        try { response = await fetch(url, { method: data ? 'POST' : 'GET', headers: { Accept: 'application/json', ...(data ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('input[name=_token]')?.value ?? '' } : {}) }, ...(data ? { body: JSON.stringify(data) } : {}), signal: AbortSignal.timeout(20000) }); }
        catch { throw new Error('Layanan belum dapat dijangkau. Periksa koneksi dan coba kembali.'); }
        const body = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(body.errors ?? {}).flat()[0] ?? body.message ?? 'Layanan belum dapat dijangkau.');
        return body;
    }
    root.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); }));
    const refresh = $('[data-refresh-seconds]');
    if (refresh) setInterval(() => {
        const interacting = root.querySelector('input:focus,textarea:focus,select:focus,button:focus,summary:focus,details[open]');
        if (!document.hidden && !interacting && !root.querySelector('[data-submitting]')) window.location.reload();
    }, Number(refresh.dataset.refreshSeconds) * 1000);

    const compose = $('[data-compose]');
    if (compose) {
        const selected = new Set(JSON.parse(compose.dataset.selected)); let page = 1, lastPage = 1, current = [], templatePage = 1, busy = false, queryVersion = 0, templatesLoaded = false;
        const error = (message = '') => { $('[data-compose-error]').textContent = message; $('[data-compose-error]').hidden = !message; };
        const invalidate = () => { compose.elements.previewToken.value = ''; $('[data-create]').disabled = true; $('[data-final-preview]').hidden = true; };
        const countCharacters = () => { $('[data-character-count]').textContent = `${compose.elements.content.value.length.toLocaleString('id-ID')} / 4.000`; };
        function sync() {
            $('[data-selected-count]').textContent = selected.size;
            $('[data-contact-inputs]').replaceChildren(...[...selected].map((id) => { const input = make('input'); input.type = 'hidden'; input.name = 'contactIds[]'; input.value = id; return input; }));
        }
        async function loadRecipients() {
            const version = ++queryVersion;
            current = []; $('[data-select-page]').disabled = true; $('[data-recipient-prev]').disabled = true; $('[data-recipient-next]').disabled = true; $('[data-recipient-list]').setAttribute('aria-busy', 'true');
            const url = new URL(compose.dataset.recipientsUrl, window.location.origin); url.searchParams.set('page', page); url.searchParams.set('search', $('[data-recipient-search]').value);
            try {
                const result = await request(url); if (version !== queryVersion) return;
                current = result.items; lastPage = result.pagination.lastPage;
                $('[data-recipient-list]').replaceChildren(...current.map((contact) => {
                    const label = make('label'), input = make('input'), text = make('span'); input.type = 'checkbox'; input.checked = selected.has(contact.id);
                    text.className = 'messaging-recipient-text'; text.append(make('strong', contact.fullName), make('small', `+${contact.phoneNormalized}`));
                    input.addEventListener('change', () => { if (input.checked && selected.size >= 1000) { input.checked = false; return error('Maksimal 1.000 penerima per campaign.'); } input.checked ? selected.add(contact.id) : selected.delete(contact.id); sync(); invalidate(); });
                    label.append(input, text); return label;
                }));
                if (!current.length) $('[data-recipient-list]').append(make('p', 'Tidak ada penerima yang cocok.'));
                $('[data-recipient-page]').textContent = `Halaman ${page}/${lastPage}`;
                $('[data-recipient-prev]').disabled = page <= 1; $('[data-recipient-next]').disabled = page >= lastPage; error();
            } catch (caught) { if (version === queryVersion) { $('[data-recipient-list]').replaceChildren(make('p', 'Daftar kontak belum dapat dimuat. Coba cari kembali.')); error(caught.message); } }
            finally { if (version === queryVersion) { $('[data-recipient-list]').setAttribute('aria-busy', 'false'); $('[data-select-page]').disabled = !current.length; } }
        }
        $('[data-recipient-load]').onclick = () => { page = 1; void loadRecipients(); };
        $('[data-recipient-search]').onkeydown = (event) => { if (event.key === 'Enter') { event.preventDefault(); page = 1; void loadRecipients(); } };
        $('[data-recipient-prev]').onclick = () => { page--; void loadRecipients(); };
        $('[data-recipient-next]').onclick = () => { page++; void loadRecipients(); };
        $('[data-select-page]').onclick = () => { current.forEach((c) => { if (selected.size < 1000) selected.add(c.id); }); sync(); invalidate(); void loadRecipients(); };
        $('[data-clear-selection]').onclick = () => { selected.clear(); sync(); invalidate(); void loadRecipients(); };
        async function loadTemplates() {
            const url = new URL(compose.dataset.recipientsUrl, window.location.origin); url.searchParams.set('resource', 'templates'); url.searchParams.set('page', templatePage);
            try { const result = await request(url); $('[data-template-list]').replaceChildren(...result.items.filter((t) => t.isActive).map((template) => { const button = make('button', template.name); button.type = 'button'; button.className = 'btn btn-secondary'; button.onclick = () => { compose.elements.content.value = template.content; compose.elements.templateId.value = template.id; countCharacters(); invalidate(); }; return button; })); if (!$('[data-template-list]').children.length) $('[data-template-list]').append(make('p', 'Tidak ada template aktif pada halaman ini.')); $('[data-template-more]').hidden = templatePage >= result.pagination.lastPage; templatesLoaded = true; }
            catch (caught) { error(caught.message); }
        }
        $('[data-template-more]').onclick = () => { templatePage++; void loadTemplates(); };
        $('.messaging-template-picker').addEventListener('toggle', (event) => { if (event.target.open && !templatesLoaded) void loadTemplates(); });
        compose.addEventListener('input', (event) => { if (['name','content','batchSize','useBanner','useInteractiveCta'].includes(event.target.name)) invalidate(); countCharacters(); $('[data-banner]').hidden = !compose.elements.useBanner.checked; });
        const payload = () => ({ name: compose.elements.name.value, content: compose.elements.content.value, ...(compose.elements.templateId.value ? { templateId: compose.elements.templateId.value } : {}), batchSize: Number(compose.elements.batchSize.value), contactIds: [...selected], useBanner: compose.elements.useBanner.checked, useInteractiveCta: compose.elements.useInteractiveCta.checked });
        $('[data-preview]').onclick = async () => {
            if (busy || !compose.reportValidity()) return;
            if (!selected.size) return error('Pilih minimal satu penerima terlebih dahulu.');
            busy = true; $('[data-preview]').disabled = true; $('[data-preview]').textContent = 'Meninjau…'; invalidate(); error();
            const before = JSON.stringify(payload());
            try {
                const result = await request(compose.dataset.previewUrl, payload());
                if (before !== JSON.stringify(payload())) throw new Error('Form berubah. Tinjau kembali pesan.');
                const panel = $('[data-final-preview]'); panel.replaceChildren(make('strong', `${result.recipientCount} penerima`));
                for (const sample of result.samples) panel.append(make('h3', sample.fullName), make('p', sample.renderedMessage));
                if (result.useBanner) panel.append(make('p', 'Pesan dilengkapi banner.'));
                if (result.useInteractiveCta) panel.append(make('p', `Tombol: ${result.ctaLabel}`));
                panel.hidden = false; compose.elements.previewToken.value = result.previewToken; $('[data-create]').disabled = false;
            } catch (caught) { error(caught.message); }
            finally { busy = false; $('[data-preview]').disabled = false; $('[data-preview]').textContent = 'Tinjau pesan'; }
        };
        compose.onsubmit = (event) => { if (busy || !compose.elements.previewToken.value) return event.preventDefault(); compose.dataset.submitting = 'true'; $('[data-create]').disabled = true; $('[data-create]').textContent = 'Membuat draft…'; $('[data-preview]').disabled = true; busy = true; };
        sync(); countCharacters(); $('[data-banner]').hidden = !compose.elements.useBanner.checked; void loadRecipients();
    }
    const importFile = $('[data-import-file]');
    if (importFile) {
        let rows = [], importing = false;
        $('[data-toggle-import]').onclick = () => { const panel = $('[data-import-panel]'); panel.hidden = !panel.hidden; $('[data-toggle-import]').setAttribute('aria-expanded', String(!panel.hidden)); if (!panel.hidden) { panel.scrollIntoView({ block: 'start' }); importFile.focus({ preventScroll: true }); } };
        importFile.onchange = async () => {
            rows = []; $('[data-import-submit]').disabled = true;
            try { if (!importFile.files[0]) return; rows = await parseContactFile(importFile.files[0]); const list = make('div'); list.append(make('p', `${rows.length} kontak. Pratinjau maksimal 20 baris:`)); rows.slice(0,20).forEach((row) => list.append(make('p', `${row.rowNumber}. ${row.fullName} | ${row.phone} | ${row.whatsappOptIn ? 'Setuju' : 'Belum setuju'}`))); $('[data-import-preview]').replaceChildren(list); $('[data-import-submit]').disabled = false; }
            catch (caught) { $('[data-import-preview]').textContent = caught.message; }
        };
        $('[data-import-submit]').onclick = async () => {
            if (importing || !rows.length || !window.confirm(`Import ${rows.length} kontak? Duplikat akan dilewati.`)) return;
            importing = true; $('[data-import-submit]').disabled = true; importFile.disabled = true;
            try { const result = await request($('[data-import-submit]').dataset.url, {rows}); rows = []; const panel = $('[data-import-preview]'); panel.replaceChildren(make('p', `${result.imported} berhasil | ${result.duplicates} duplikat | ${result.invalid} perlu diperbaiki`)); result.issues.forEach((issue) => panel.append(make('p', `Baris ${issue.rowNumber}: ${issue.message}`))); panel.append(make('a', 'Segarkan daftar kontak')); panel.lastChild.href = window.location.href; }
            catch (caught) { $('[data-import-preview]').textContent = caught.message; }
            finally { importing = false; importFile.disabled = false; $('[data-import-submit]').disabled = !rows.length; }
        };
    }
}
