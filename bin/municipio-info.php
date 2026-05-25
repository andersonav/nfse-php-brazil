<?php

declare(strict_types=1);

use Alves\NfseBrasil\Tools;

require __DIR__ . '/../vendor/autoload.php';

$args = $argv;
array_shift($args);

$ibgeOrAlias = $args[0] ?? null;
if (!$ibgeOrAlias) {
    fwrite(STDERR, "Uso: php bin/municipio-info.php <ibge-ou-alias> [--json]\n");
    exit(1);
}

$asJson = in_array('--json', $args, true);

$config = [
    'tpamb' => 2,
    'prefeitura' => (string) $ibgeOrAlias,
    'catalog_compiled_path' => __DIR__ . '/../storage/municipios-catalog.php',
];

$tools = new Tools((string) json_encode($config));
$info = $tools->detalhesMunicipio((string) $ibgeOrAlias);

if ($asJson) {
    echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

print_r($info);
