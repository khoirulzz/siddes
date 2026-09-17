import { test } from 'node:test';
import assert from 'node:assert/strict';
import { parseCsv, mapContactRows } from './messaging-import.js';

test('quoted CSV, BOM, explicit consent and blank consent', () => {
    const rows = mapContactRows(parseCsv('\uFEFFNama Lengkap;Nomor WhatsApp;Opt In\n"Rumah, Satu";081234567890;ya\nRumah Dua;081234567891;'));
    assert.equal(rows[0].fullName, 'Rumah, Satu');
    assert.equal(rows[0].whatsappOptIn, true);
    assert.equal(rows[1].whatsappOptIn, false);
});
test('invalid quotes and more than 1000 rows are rejected', () => {
    assert.throws(() => parseCsv('nama,nomor\n"tidak ditutup'));
    assert.throws(() => mapContactRows([['Nama','Nomor'], ...Array.from({ length: 1001 }, () => ['Rumah','081234567890'])]));
});
test('Persetujuan header supports explicit consent without changing blank consent', () => {
    const rows = mapContactRows(parseCsv('Nama Lengkap,Nomor WhatsApp,Persetujuan\nRumah Satu,081234567890,Ya\nRumah Dua,081234567891,Tidak\nRumah Tiga,081234567892,'));
    assert.deepEqual(rows.map((row) => row.whatsappOptIn), [true, false, false]);
});
