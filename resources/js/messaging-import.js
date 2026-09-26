const NAME_HEADERS = new Set(['nama', 'nama lengkap', 'nama penerima', 'name', 'full name']);
const PHONE_HEADERS = new Set(['nomor whatsapp', 'no whatsapp', 'nomor wa', 'no wa', 'nomor telepon', 'no telepon', 'phone', 'nomor hp', 'no hp', 'whatsapp', 'nomor', 'telepon', 'telp']);
const OPT_IN_HEADERS = new Set(['persetujuan', 'izin', 'opt in', 'whatsapp opt in', 'izin whatsapp', 'bersedia']);
const MAX_HEADER_SCAN_ROWS = 1000;
const MAX_HEADER_SCAN_NONEMPTY_ROWS = 25;

const normalize = (value) => String(value ?? '').replace(/^\uFEFF/, '').toLowerCase().replace(/\([^)]*\)/g, ' ').replace(/[_.\-:*]+/g, ' ').replace(/\s+/g, ' ').trim();
const cellText = (value) => value instanceof Date ? value.toISOString() : String(value ?? '').trim();
const hasValue = (row) => row.some((value) => cellText(value));
const phoneDigitCount = (value) => value.replace(/\D/g, '').length;

function optInValue(value) {
    const normalized = normalize(value);
    if (!normalized) return true;
    if (['tidak', 'no', 'false', '0', 'tidak setuju', 'menolak', 'opt out', 'optout'].includes(normalized)) return false;
    return ['ya', 'yes', 'true', '1', 'setuju', 'bersedia', 'opt in', 'optin'].includes(normalized);
}

function headerIndexes(row) {
    const headers = row.map(normalize);
    return {
        name: headers.findIndex((header) => NAME_HEADERS.has(header)),
        phone: headers.findIndex((header) => PHONE_HEADERS.has(header)),
        opt: headers.findIndex((header) => OPT_IN_HEADERS.has(header)),
    };
}

function findHeaderRow(rows) {
    let nonemptyRows = 0;
    for (let index = 0; index < Math.min(rows.length, MAX_HEADER_SCAN_ROWS); index++) {
        const row = rows[index] ?? [];
        if (!hasValue(row)) continue;
        const { name, phone } = headerIndexes(row);
        if (name >= 0 && phone >= 0) return index;
        if (++nonemptyRows >= MAX_HEADER_SCAN_NONEMPTY_ROWS) break;
    }
    return -1;
}
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
function mapRows(rows, sheetName, headerRowIndex = 0) {
    const { name, phone, opt } = headerIndexes(rows[headerRowIndex] ?? []);
    if (name < 0 || phone < 0) throw new Error('Kolom Nama Lengkap dan Nomor WhatsApp wajib ada.');
    let skipped = 0, sourceRows = 0;
    const mapped = rows.slice(headerRowIndex + 1).flatMap((row, index) => {
        if (!hasValue(row)) return [];
        sourceRows++;
        const fullName = cellText(row[name]), phoneValue = cellText(row[phone]);
        const digits = phoneDigitCount(phoneValue);
        if (!fullName || !phoneValue || digits < 10 || digits > 14) { skipped++; return []; }
        return [{ ...(sheetName ? { sheetName } : {}), rowNumber: headerRowIndex + index + 2, fullName, phone: phoneValue, whatsappOptIn: opt < 0 ? true : optInValue(row[opt]) }];
    });
    return { rows: mapped, skipped, sourceRows };
}

export function mapContactRows(rows) {
    const mapped = mapRows(rows);
    if (mapped.sourceRows > 1000) throw new Error('Maksimal 1.000 baris per file impor.');
    if (!mapped.rows.length) throw new Error('Tidak ada baris dengan nama dan nomor WhatsApp yang valid.');
    return mapped.rows;
}

export function mapContactSheets(sheets) {
    const inspected = sheets.map(({ sheet, data }) => ({ sheet, data, headerRowIndex: findHeaderRow(data) }));
    const contactSheets = inspected.filter(({ headerRowIndex }) => headerRowIndex >= 0);
    const ignoredSheets = inspected.filter(({ headerRowIndex }) => headerRowIndex < 0).map(({ sheet, data }) => ({
        sheetName: sheet,
        reason: data.some(hasValue) ? 'header Nama Lengkap dan Nomor WhatsApp tidak ditemukan di awal sheet' : 'sheet kosong',
    }));
    if (!contactSheets.length) throw new Error(`Tidak ada sheet dengan header Nama Lengkap dan Nomor WhatsApp. Sheet yang diperiksa: ${sheets.map(({ sheet }) => sheet).join(', ') || 'tidak ada'}.`);

    const mapped = contactSheets.map(({ sheet, data, headerRowIndex }) => ({ sheet, headerRowIndex, ...mapRows(data, sheet, headerRowIndex) }));
    const sourceRows = mapped.reduce((total, item) => total + item.sourceRows, 0);
    if (sourceRows > 1000) throw new Error('Maksimal 1.000 baris untuk total seluruh sheet kontak.');
    const rows = mapped.flatMap((item) => item.rows);
    if (!rows.length) throw new Error('Tidak ada baris dengan nama dan nomor WhatsApp yang valid.');
    return {
        rows,
        skipped: mapped.reduce((total, item) => total + item.skipped, 0),
        sheetSummaries: mapped.map(({ sheet, headerRowIndex, rows: sheetRows, skipped }) => ({ sheetName: sheet, headerRow: headerRowIndex + 1, ready: sheetRows.length, skipped })),
        ignoredSheets,
    };
}

export async function parseContactFile(file) {
    if (file.size > 5 * 1024 * 1024) throw new Error('Ukuran file maksimal 5 MB.');
    const extension = file.name.split('.').pop()?.toLowerCase();
    if (extension === 'csv') {
        const parsed = parseCsv(await file.text());
        const mapped = mapRows(parsed);
        if (mapped.sourceRows > 1000) throw new Error('Maksimal 1.000 baris per file impor.');
        if (!mapped.rows.length) throw new Error('Tidak ada baris dengan nama dan nomor WhatsApp yang valid.');
        return { rows: mapped.rows, skipped: mapped.skipped, sheetSummaries: [], ignoredSheets: [] };
    }
    if (extension === 'xlsx') {
        const { default: readExcelFile } = await import('read-excel-file/browser');
        return mapContactSheets(await readExcelFile(file));
    }
    throw new Error('Pilih file CSV atau XLSX.');
}
