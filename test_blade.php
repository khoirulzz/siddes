<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$compiler = app('blade.compiler');
$compiled = $compiler->compileString(file_get_contents(__DIR__.'/resources/views/layouts/public.blade.php'));
file_put_contents(__DIR__.'/compiled_public.php', $compiled);
echo "Compiled!\n";
