<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Alves\NfseBrasil\Tools;
use NFePHP\Common\Certificate;

$config = json_encode([
    'tpamb' => 2,
    'prefeitura' => '3550308',
    'catalog_compiled_path' => __DIR__ . '/../storage/municipios-acbr.php',
]);

$pfxPath = __DIR__ . '/../certificado.pfx';
$pfxPass = 'senha';
$cert = null;
if (is_file($pfxPath)) {
    $cert = Certificate::readPfx(file_get_contents($pfxPath), $pfxPass);
}

$tools = new Tools($config, $cert);

// Use uma chave real para gerar o PDF.
$chave = 'CHAVE_DA_NFSE';
$output = __DIR__ . '/../storage/danfse-gerado.pdf';
$tools->gerarDanfsePdfPorChave($chave, $output);

echo "DANFSE gerado em: {$output}\n";

