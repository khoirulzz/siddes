const normalize = (value) => String(value ?? '').replace(/^\uFEFF/, '').toLowerCase().replace(/[_.-]+/g, ' ').replace(/\s+/g, ' ').trim();
export function parseCsv(text) {
    const first = text.split(/\r?\n/).find((line) => line.trim()) ?? '';
    let headerQuoted = false, commas = 0, semicolons = 0;
    for (let i = 0; i < first.length; i++) {
        if (first[i] === '"' && headerQuoted && first[i + 1] === '"') i++;
        else if (first[i] === '"') headerQuoted = !headerQuoted;
        else if (!headerQuoted && first[i] === ',') commas++;
        else if (!headerQuoted && first[i] === ';') semicolons++;
    }
    const delimiter = semicolons > commas ? ';' : ',';
    const rows = []; let row = [], cell = '', quoted = false;
    for (let i = 0; i < text.length; i++) {
        const char = text[i];
        if (char === '"' && quoted && text[i + 1] === '"') { cell += '"'; i++; }
        else if (char === '"') quoted = !quoted;
        else if (char === delimiter && !quoted) { row.push(cell); cell = ''; }
        else if (/\r|\n/.test(char) && !quoted) { if (char === '\r' && text[i + 1] === '\n') i++; row.push(cell); if (row.some((v) => v.trim())) rows.push(row); row = []; cell = ''; }
        else cell += char;
    }
    if (quoted) throw new Error('Tanda kutip CSV belum ditutup.');
    row.push(cell); if (row.some((v) => v.trim())) rows.push(row);
    return rows;
}
export function mapContactRows(rows) {
    const headers = (rows[0] ?? []).map(normalize);
    const name = headers.findIndex((h) => ['nama','nama lengkap','nama penerima','name','full name'].includes(h));
    const phone = headers.findIndex((h) => ['nomor whatsapp','no whatsapp','nomor telepon','no telepon','phone','nomor hp','no hp','whatsapp','nomor','telepon','telp'].includes(h));
    const opt = headers.findIndex((h) => ['persetujuan','izin','opt in','whatsapp opt in','izin whatsapp','bersedia'].includes(h));
    if (name < 0 || phone < 0) throw new Error('Kolom Nama Lengkap dan Nomor WhatsApp wajib ada.');
    const result = rows.slice(1).map((row, index) => ({ rowNumber: index + 2, fullName: String(row[name] ?? '').trim(), phone: String(row[phone] ?? '').trim(), whatsappOptIn: opt >= 0 && ['ya','yes','true','1','setuju','bersedia','opt in','optin'].includes(normalize(row[opt])) })).filter((row) => row.fullName || row.phone);
    if (!result.length || result.length > 1000) throw new Error('File harus berisi 1–1.000 kontak.');
    return result;
}
export async function parseContactFile(file) {
    if (file.size > 5 * 1024 * 1024) throw new Error('Ukuran file maksimal 5 MB.');
    const extension = file.name.split('.').pop().toLowerCase();
    if (extension === 'csv') return mapContactRows(parseCsv(await file.text()));
    if (extension === 'xlsx') { const { readSheet } = await import('read-excel-file/browser'); return mapContactRows(await readSheet(file)); }
    throw new Error('Pilih file CSV atau XLSX.');
}
