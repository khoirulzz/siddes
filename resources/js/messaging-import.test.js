import { test } from 'node:test';
import assert from 'node:assert/strict';
import { strToU8, zipSync } from 'fflate';
import { parseCsv, mapContactRows, mapContactSheets, parseContactFile } from './messaging-import.js';

function makeWorkbook(sheets) {
    const xml = (value) => value.replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&apos;' })[character]);
    const files = {};
    const sheetEntries = sheets.map(({ name }, index) => `<sheet name="${xml(name)}" sheetId="${index + 1}" r:id="rId${index + 1}"/>`).join('');
    const relationships = sheets.map((_, index) => `<Relationship Id="rId${index + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet${index + 1}.xml"/>`).join('');
    const sheetTypes = sheets.map((_, index) => `<Override PartName="/xl/worksheets/sheet${index + 1}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>`).join('');
    files['[Content_Types].xml'] = strToU8(`<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>${sheetTypes}</Types>`);
    files['_rels/.rels'] = strToU8('<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    files['xl/workbook.xml'] = strToU8(`<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>${sheetEntries}</sheets></workbook>`);
    files['xl/_rels/workbook.xml.rels'] = strToU8(`<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">${relationships}</Relationships>`);
    for (const [index, sheet] of sheets.entries()) {
        const rows = sheet.rows.map((cells, rowIndex) => `<row r="${rowIndex + 1}">${cells.map((value, columnIndex) => `<c r="${String.fromCharCode(65 + columnIndex)}${rowIndex + 1}" t="inlineStr"><is><t>${xml(value)}</t></is></c>`).join('')}</row>`).join('');
        files[`xl/worksheets/sheet${index + 1}.xml`] = strToU8(`<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>${rows}</sheetData></worksheet>`);
    }
    return new File([Uint8Array.from(zipSync(files)).buffer], 'kontak.xlsx', { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
}

test('quoted CSV, BOM, explicit consent and blank consent', () => {
    const rows = mapContactRows(parseCsv('\uFEFFNama Lengkap;Nomor WhatsApp;Opt In\n"Rumah, Satu";081234567890;ya\nRumah Dua;081234567891;'));
    assert.equal(rows[0].fullName, 'Rumah, Satu');
    assert.equal(rows[0].whatsappOptIn, true);
    assert.equal(rows[1].whatsappOptIn, true);
});
test('invalid quotes and more than 1000 rows are rejected', () => {
    assert.throws(() => parseCsv('nama,nomor\n"tidak ditutup'));
    assert.throws(() => mapContactRows([['Nama','Nomor'], ...Array.from({ length: 1001 }, () => ['Rumah','081234567890'])]));
});
test('Persetujuan kosong mengikuti default Ya dan penolakan eksplisit tetap Tidak', () => {
    const rows = mapContactRows(parseCsv('Nama Lengkap,Nomor WhatsApp,Persetujuan\nRumah Satu,081234567890,Ya\nRumah Dua,081234567891,Tidak\nRumah Tiga,081234567892,'));
    assert.deepEqual(rows.map((row) => row.whatsappOptIn), [true, false, true]);
});

test('sheet kelima yang baru diisi ikut terbaca dan sheet tanpa header dilaporkan', () => {
    const parsed = mapContactSheets([
        { sheet: 'Dusun 1', data: [['Nama Lengkap', 'Nomor WhatsApp'], ['Satu', '081234567801']] },
        { sheet: 'Dusun 2', data: [['Nama Lengkap', 'Nomor WhatsApp'], ['Dua', '081234567802']] },
        { sheet: 'Petunjuk', data: [['Cara mengisi']] },
        { sheet: 'Masih Kosong', data: [] },
        { sheet: 'Dusun 5', data: [[], ['Daftar baru'], ['Nama Lengkap *', 'No. WA (wajib)', 'Persetujuan'], ['Lima', '081234567805', '']] },
    ]);
    assert.deepEqual(parsed.rows.map(({ sheetName, rowNumber, fullName }) => [sheetName, rowNumber, fullName]), [
        ['Dusun 1', 2, 'Satu'], ['Dusun 2', 2, 'Dua'], ['Dusun 5', 4, 'Lima'],
    ]);
    assert.equal(parsed.rows[2].whatsappOptIn, true);
    assert.deepEqual(parsed.ignoredSheets.map(({ sheetName }) => sheetName), ['Petunjuk', 'Masih Kosong']);
    assert.deepEqual(parsed.sheetSummaries.map(({ sheetName, headerRow }) => [sheetName, headerRow]), [['Dusun 1', 1], ['Dusun 2', 1], ['Dusun 5', 3]]);
});

test('alur unggah benar-benar membaca semua sheet XLSX', async () => {
    const parsed = await parseContactFile(makeWorkbook([
        { name: 'Dusun 1', rows: [['Nama Lengkap', 'Nomor WhatsApp'], ['Satu', '081234567801']] },
        { name: 'Dusun 2', rows: [['Nama Lengkap', 'Nomor WhatsApp'], ['Dua', '081234567802']] },
        { name: 'Petunjuk', rows: [['Cara mengisi']] },
        { name: 'Masih Kosong', rows: [] },
        { name: 'Dusun 5', rows: [[], ['Daftar baru'], ['Nama Lengkap', 'Nomor WhatsApp'], ['Lima', '081234567805']] },
    ]));
    assert.deepEqual(parsed.rows.map(({ sheetName, fullName }) => [sheetName, fullName]), [['Dusun 1', 'Satu'], ['Dusun 2', 'Dua'], ['Dusun 5', 'Lima']]);
});
