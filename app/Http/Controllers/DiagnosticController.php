<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DiagnosticController — endpoint sementara untuk verifikasi PHP2Shell patch di Render.com.
 *
 * CARA PAKAI:
 *   1. Set env var DIAG_SECRET di Render Dashboard (nilai rahasia sesukamu)
 *   2. Deploy, lalu akses: https://<domain>/diag?token=<nilai-DIAG_SECRET>
 *   3. Periksa hasil JSON — pastikan php_version >= 8.3.36
 *   4. Setelah selesai: hapus file ini + hapus rute di web.php + hapus env DIAG_SECRET
 *
 * ⚠️  JANGAN tinggalkan endpoint ini aktif di production lebih dari yang perlu.
 */
class DiagnosticController extends Controller
{
    public function run(Request $request): JsonResponse
    {
        // Guard: wajib token yang cocok dengan config security.diag_secret (dari env DIAG_SECRET)
        $secret = (string) config('security.diag_secret', env('DIAG_SECRET', ''));
        $provided = (string) $request->query('token', '');

        if ($secret === '' || ! hash_equals($secret, $provided)) {
            abort(403, 'Forbidden');
        }

        $phpVersion = PHP_VERSION;

        // PHP2Shell patch check: aman jika >= 8.3.36 / 8.4.27 / 8.5.12
        $safeVersions = [
            '8.2' => '8.2.35',
            '8.3' => '8.3.36',
            '8.4' => '8.4.27',
            '8.5' => '8.5.12',
        ];

        $major = implode('.', array_slice(explode('.', $phpVersion), 0, 2)); // e.g. "8.3"
        $isPatched = false;
        $minimumSafe = $safeVersions[$major] ?? null;

        if ($minimumSafe !== null) {
            $isPatched = version_compare($phpVersion, $minimumSafe, '>=');
        }

        // session.upload_progress check (CVE-2026-109619 vector)
        $uploadProgressEnabled = ini_get('session.upload_progress.enabled');
        $uploadProgressOff = in_array(strtolower((string) $uploadProgressEnabled), ['', '0', 'off', 'false'], true);

        // expose_php check
        $exposePhp = ini_get('expose_php');
        $exposePhpOff = in_array(strtolower((string) $exposePhp), ['', '0', 'off', 'false'], true);

        // display_errors check
        $displayErrors = ini_get('display_errors');
        $displayErrorsOff = in_array(strtolower((string) $displayErrors), ['', '0', 'off', 'false'], true);

        // memory_limit
        $memoryLimit = ini_get('memory_limit');

        // Check loaded extensions
        $criticalExtensions = ['pdo_mysql', 'gd', 'zip', 'opcache', 'bcmath', 'mbstring'];
        $loadedExts = [];
        foreach ($criticalExtensions as $ext) {
            $loadedExts[$ext] = extension_loaded($ext);
        }

        // Server software info
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? php_uname('s') . ' ' . php_uname('r');

        // Overall PHP2Shell safety assessment
        $php2shellSafe = $isPatched && $uploadProgressOff;

        return response()->json([
            'status'       => $php2shellSafe ? 'AMAN ✅' : 'PERLU TINDAKAN ⚠️',
            'timestamp'    => now()->toIso8601String(),
            'php2shell' => [
                'cve_2026_109618' => $isPatched ? 'PATCHED ✅' : 'VULNERABLE ⚠️',
                'cve_2026_109619' => $uploadProgressOff ? 'MITIGATED ✅' : 'CHECK NEEDED ⚠️',
            ],
            'php' => [
                'version'         => $phpVersion,
                'major_branch'    => $major,
                'minimum_safe'    => $minimumSafe ?? 'unknown',
                'is_patched'      => $isPatched,
                'zend_version'    => zend_version(),
            ],
            'ini' => [
                'session_upload_progress_enabled' => $uploadProgressEnabled === '' ? 'Off' : $uploadProgressEnabled,
                'upload_progress_off'             => $uploadProgressOff,
                'expose_php'                      => $exposePhp === '' ? 'Off' : $exposePhp,
                'expose_php_off'                  => $exposePhpOff,
                'display_errors'                  => $displayErrors === '' ? 'Off' : $displayErrors,
                'display_errors_off'              => $displayErrorsOff,
                'memory_limit'                    => $memoryLimit,
                'max_input_nesting_level'         => ini_get('max_input_nesting_level'),
            ],
            'extensions' => $loadedExts,
            'server' => [
                'software'    => $serverSoftware,
                'php_sapi'    => PHP_SAPI,
                'os'          => PHP_OS,
                'environment' => config('app.env'),
            ],
            'laravel' => [
                'version'   => app()->version(),
                'debug'     => config('app.debug'),
                'app_env'   => config('app.env'),
            ],
            'note' => '⚠️ Hapus endpoint ini setelah verifikasi selesai!',
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

