import { parseContactFile } from './messaging-import';

const root = document.querySelector('[data-messaging-module]');
if (root) {
    const $ = (selector) => root.querySelector(selector);
    const make = (tag, text) => { const node = document.createElement(tag); if (text !== undefined) node.textContent = text; return node; };
    async function request(url, data) {
        const response = await fetch(url, { method: data ? 'POST' : 'GET', headers: { Accept: 'application/json', ...(data ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('input[name=_token]')?.value ?? '' } : {}) }, ...(data ? { body: JSON.stringify(data) } : {}), signal: AbortSignal.timeout(20000) });
        const body = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(body.message ?? 'Layanan belum dapat dijangkau.');
        return body;
    }
    root.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); }));
    const refresh = $('[data-refresh-seconds]');
    if (refresh) setInterval(() => { if (!document.hidden && !document.querySelector('input:focus,textarea:focus') && !root.querySelector('[data-submitting]')) window.location.reload(); }, Number(refresh.dataset.refreshSeconds) * 1000);

    const compose = $('[data-compose]');
    if (compose) {
        const selected = new Set(JSON.parse(compose.dataset.selected)); let page = 1, lastPage = 1, current = [], templatePage = 1, busy = false, queryVersion = 0;
        const error = (message = '') => { $('[data-compose-error]').textContent = message; $('[data-compose-error]').hidden = !message; };
        const invalidate = () => { compose.elements.previewToken.value = ''; $('[data-create]').disabled = true; $('[data-final-preview]').hidden = true; };
        function sync() {
            $('[data-selected-count]').textContent = selected.size;
            $('[data-contact-inputs]').replaceChildren(...[...selected].map((id) => { const input = make('input'); input.type = 'hidden'; input.name = 'contactIds[]'; input.value = id; return input; }));
        }
        async function loadRecipients() {
            const version = ++queryVersion;
            const url = new URL(compose.dataset.recipientsUrl, window.location.origin); url.searchParams.set('page', page); url.searchParams.set('search', $('[data-recipient-search]').value);
            try {
                const result = await request(url); if (version !== queryVersion) return;
                current = result.items; lastPage = result.pagination.lastPage;
                $('[data-recipient-list]').replaceChildren(...current.map((contact) => {
                    const label = make('label'), input = make('input'); input.type = 'checkbox'; input.checked = selected.has(contact.id);
                    input.addEventListener('change', () => { if (input.checked && selected.size >= 1000) { input.checked = false; return error('Maksimal 1.000 penerima per campaign.'); } input.checked ? selected.add(contact.id) : selected.delete(contact.id); sync(); invalidate(); });
                    label.append(input, make('span', `${contact.fullName} (+${contact.phoneNormalized})`)); return label;
                }));
                if (!current.length) $('[data-recipient-list]').append(make('p', 'Tidak ada kontak eligible yang cocok.'));
                $('[data-recipient-page]').textContent = `Halaman ${page}/${lastPage}`;
                $('[data-recipient-prev]').disabled = page <= 1; $('[data-recipient-next]').disabled = page >= lastPage; error();
            } catch (caught) { error(caught.message); }
        }
        $('[data-recipient-load]').onclick = () => { page = 1; void loadRecipients(); };
        $('[data-recipient-search]').onkeydown = (event) => { if (event.key === 'Enter') { event.preventDefault(); page = 1; void loadRecipients(); } };
        $('[data-recipient-prev]').onclick = () => { page--; void loadRecipients(); };
        $('[data-recipient-next]').onclick = () => { page++; void loadRecipients(); };
        $('[data-select-page]').onclick = () => { current.forEach((c) => { if (selected.size < 1000) selected.add(c.id); }); sync(); invalidate(); void loadRecipients(); };
        $('[data-clear-selection]').onclick = () => { selected.clear(); sync(); invalidate(); void loadRecipients(); };
        async function loadTemplates() {
            const url = new URL(compose.dataset.recipientsUrl, window.location.origin); url.searchParams.set('resource', 'templates'); url.searchParams.set('page', templatePage);
            try { const result = await request(url); $('[data-template-list]').replaceChildren(...result.items.filter((t) => t.isActive).map((template) => { const button = make('button', template.name); button.type = 'button'; button.className = 'btn btn-secondary'; button.onclick = () => { compose.elements.content.value = template.content; compose.elements.templateId.value = template.id; invalidate(); }; return button; })); $('[data-template-more]').hidden = templatePage >= result.pagination.lastPage; }
            catch (caught) { error(caught.message); }
        }
        $('[data-template-more]').onclick = () => { templatePage++; void loadTemplates(); };
        compose.addEventListener('input', (event) => { if (!['previewToken','_token'].includes(event.target.name)) invalidate(); $('[data-banner]').hidden = !compose.elements.useBanner.checked; });
        const payload = () => ({ name: compose.elements.name.value, content: compose.elements.content.value, ...(compose.elements.templateId.value ? { templateId: compose.elements.templateId.value } : {}), batchSize: Number(compose.elements.batchSize.value), contactIds: [...selected], useBanner: compose.elements.useBanner.checked, useInteractiveCta: compose.elements.useInteractiveCta.checked });
        $('[data-preview]').onclick = async () => {
            if (busy || !compose.reportValidity()) return;
            busy = true; $('[data-preview]').disabled = true; invalidate(); error();
            const before = JSON.stringify(payload());
            try {
                const result = await request(compose.dataset.previewUrl, payload());
                if (before !== JSON.stringify(payload())) throw new Error('Form berubah. Tinjau kembali pesan.');
                const panel = $('[data-final-preview]'); panel.replaceChildren(make('strong', `${result.recipientCount} penerima`));
                for (const sample of result.samples) panel.append(make('h3', sample.fullName), make('p', sample.renderedMessage));
                if (result.useBanner) panel.append(make('p', 'Media: banner default + caption'));
                if (result.useInteractiveCta) panel.append(make('p', `Tombol: ${result.ctaLabel}`));
                panel.hidden = false; compose.elements.previewToken.value = result.previewToken; $('[data-create]').disabled = false;
            } catch (caught) { error(caught.message); }
            finally { busy = false; $('[data-preview]').disabled = false; }
        };
        compose.onsubmit = (event) => { if (busy || !compose.elements.previewToken.value) return event.preventDefault(); compose.dataset.submitting = 'true'; $('[data-create]').disabled = true; $('[data-preview]').disabled = true; busy = true; };
        sync(); $('[data-banner]').hidden = !compose.elements.useBanner.checked; void loadRecipients(); void loadTemplates();
    }
    const importFile = $('[data-import-file]');
    if (importFile) {
        let rows = [], importing = false;
        $('[data-toggle-import]').onclick = () => { $('[data-import-panel]').hidden = !$('[data-import-panel]').hidden; };
        importFile.onchange = async () => {
            rows = []; $('[data-import-submit]').disabled = true;
            try { if (!importFile.files[0]) return; rows = await parseContactFile(importFile.files[0]); const list = make('div'); list.append(make('p', `${rows.length} kontak. Pratinjau maksimal 20 baris:`)); rows.slice(0,20).forEach((row) => list.append(make('p', `${row.rowNumber}. ${row.fullName} | ${row.phone} | ${row.whatsappOptIn ? 'Opt-in' : 'Opt-out'}`))); $('[data-import-preview]').replaceChildren(list); $('[data-import-submit]').disabled = false; }
            catch (caught) { $('[data-import-preview]').textContent = caught.message; }
        };
        $('[data-import-submit]').onclick = async () => {
            if (importing || !rows.length || !window.confirm(`Import ${rows.length} kontak? Duplikat akan dilewati.`)) return;
            importing = true; $('[data-import-submit]').disabled = true; importFile.disabled = true;
            try { const result = await request($('[data-import-submit]').dataset.url, {rows}); rows = []; const panel = $('[data-import-preview]'); panel.replaceChildren(make('p', `${result.imported} berhasil | ${result.duplicates} duplikat | ${result.invalid} invalid`)); result.issues.forEach((issue) => panel.append(make('p', `Baris ${issue.rowNumber}: ${issue.message}`))); panel.append(make('a', 'Segarkan daftar kontak')); panel.lastChild.href = window.location.href; }
            catch (caught) { $('[data-import-preview]').textContent = caught.message; }
            finally { importing = false; importFile.disabled = false; $('[data-import-submit]').disabled = !rows.length; }
        };
    }
}
