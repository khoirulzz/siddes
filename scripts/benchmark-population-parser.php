<?php

// Local/read-only diagnostic. Never print resident identifiers or workbook contents.
require dirname(__DIR__).'/vendor/autoload.php';

if (! isset($argv[1]) || ! is_file($argv[1])) {
    fwrite(STDERR, "Usage: php scripts/benchmark-population-parser.php <file.xlsx>\n");
    exit(1);
}

$started = microtime(true);
try {
    $file = new \Illuminate\Http\UploadedFile(realpath($argv[1]), basename($argv[1]), null, null, true);
    $result = (new \App\Services\PopulationImportParser())->parse($file);
    echo json_encode([
        'rows' => count($result['rows']),
        'columns' => count($result['headers']),
        'elapsed_seconds' => round(microtime(true) - $started, 3),
        'php_peak_mib' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
    ], JSON_PRETTY_PRINT).PHP_EOL;
} catch (\Throwable $exception) {
    fwrite(STDERR, json_encode([
        'exception_class' => $exception::class,
        'php_peak_mib' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
    ]).PHP_EOL);
    exit(1);
}
