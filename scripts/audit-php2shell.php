<?php
/**
 * scripts/audit-php2shell.php
 *
 * CLI Auditor untuk memeriksa mitigasi PHP2Shell (CVE-2026-109618 & CVE-2026-109619)
 * Dijalankan otomatis saat build & start di Render.com sehingga hasilnya
 * langsung terlihat di dashboard Render deploy logs tanpa butuh web shell!
 */

$phpVersion = PHP_VERSION;
$safeVersions = [
    '8.1' => '8.1.99', // EOL
    '8.2' => '8.2.35',
    '8.3' => '8.3.36',
    '8.4' => '8.4.27',
    '8.5' => '8.5.12',
];

$major = implode('.', array_slice(explode('.', $phpVersion), 0, 2));
$minimumSafe = $safeVersions[$major] ?? null;
$isPatched = false;

if ($minimumSafe !== null) {
    $isPatched = version_compare($phpVersion, $minimumSafe, '>=');
}

$uploadProgress = ini_get('session.upload_progress.enabled');
$uploadProgressOff = in_array(strtolower((string)$uploadProgress), ['', '0', 'off', 'false'], true);

$displayErrors = ini_get('display_errors');
$displayErrorsOff = in_array(strtolower((string)$displayErrors), ['', '0', 'off', 'false'], true);

$exposePhp = ini_get('expose_php');
$exposePhpOff = in_array(strtolower((string)$exposePhp), ['', '0', 'off', 'false'], true);

$nesting = ini_get('max_input_nesting_level');

echo "\n";
echo "====================================================================\n";
echo "🛡️  AUDIT KEAMANAN RUNTIME PHP2SHELL (RENDER.COM)\n";
echo "====================================================================\n";
echo "PHP Version Active       : " . $phpVersion . " (" . PHP_SAPI . ")\n";
echo "Minimum Safe Threshold   : " . ($minimumSafe ?? 'Belum ditentukan') . " (Branch PHP " . $major . ")\n";

if ($isPatched) {
    echo "CVE-2026-109618 (OOM/RCE): [✅ PATCHED] Runtime PHP sudah memuat patch resmi.\n";
} else {
    echo "CVE-2026-109618 (OOM/RCE): [⚠️  PERINGATAN] Versi PHP di bawah " . ($minimumSafe ?? 'aman') . ".\n";
    echo "                           Pastikan deploy ulang menggunakan base image / nixpkgs terbaru.\n";
}

if ($uploadProgressOff) {
    echo "CVE-2026-109619 (UAF)    : [✅ TERMITIGASI] session.upload_progress.enabled = Off\n";
} else {
    echo "CVE-2026-109619 (UAF)    : [⚠️  AKTIF] session.upload_progress.enabled masih aktif (" . ($uploadProgress ?: 'On') . ").\n";
}

echo "--------------------------------------------------------------------\n";
echo "Pemeriksaan Hardening Tambahan:\n";
echo "• expose_php             : " . ($exposePhpOff ? "[✅ OFF]" : "[⚠️ ON] Disarankan Off") . "\n";
echo "• display_errors         : " . ($displayErrorsOff ? "[✅ OFF]" : "[⚠️ ON] Disarankan Off di prod") . "\n";
echo "• max_input_nesting_level: " . ($nesting ?: 'default') . "\n";
echo "• memory_limit           : " . ini_get('memory_limit') . "\n";
echo "====================================================================\n\n";

