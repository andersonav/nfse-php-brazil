<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../vendor/autoload.php';

try {
    $config = new stdClass();
    $config->tpamb = 2; // 1 - Producao, 2 - Homologacao
    $config->prefeitura = '3550308'; // IBGE ou alias
    $config->catalog_compiled_path = __DIR__ . '/../storage/municipios-acbr.php';

    $configJson = json_encode($config);
    $content = file_get_contents('certificado.pfx');
    $password = 'senha_certificado';
    $cert = \NFePHP\Common\Certificate::readPfx($content, $password);
    $tools = new \Alves\NfseBrasil\Tools($configJson, $cert);

    dd(
        'municipioSuportado',
        $tools->municipioSuportado(),
        'diagnostico',
        $tools->diagnosticoMunicipio(),
        'provedoresImplementados',
        $tools->getSupportedProviders()
    );
} catch (Exception $e) {
    dd($e->getMessage(), $e);
}
