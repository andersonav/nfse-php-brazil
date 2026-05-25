<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../vendor/autoload.php';

try {
    $config = new stdClass();
    $config->tpamb = 2; // 1 - producao, 2 - homologacao
    $config->prefeitura = '3550308'; // IBGE ou alias
    $config->catalog_compiled_path = __DIR__ . '/../storage/municipios-acbr.php';

    $tools = new \Alves\NfseBrasil\Tools(json_encode($config));

    $detalhesAtual = $tools->detalhesMunicipio();
    $detalhesRecife = $tools->detalhesMunicipio('2611606');
    $listaSP = $tools->listarMunicipios('SP', null, 10);

    dd([
        'municipio_configurado' => $detalhesAtual,
        'municipio_consultado' => $detalhesRecife,
        'primeiros_10_sp' => $listaSP,
    ]);
} catch (Exception $e) {
    dd($e->getMessage(), $e);
}
