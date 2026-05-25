<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../vendor/autoload.php';

try {
    $config = new stdClass();
    $config->tpamb = 2; // 1 - producao, 2 - homologacao
    $config->prefeitura = '3550308'; // Sao Paulo/SP (ISSSaoPaulo)
    $config->catalog_compiled_path = __DIR__ . '/../storage/municipios-acbr.php';

    $configJson = json_encode($config);
    $content = file_get_contents('certificado.pfx');
    $password = 'senha_certificado';
    $cert = \NFePHP\Common\Certificate::readPfx($content, $password);
    $tools = new \Alves\NfseBrasil\Tools($configJson, $cert);

    $payload = [
        // A prefeitura de Sao Paulo usa tags proprias com VersaoSchema + MensagemXML.
        // Se quiser, informe o XML final da MensagemXML manualmente.
        'versao_schema' => '1',
        'mensagem_xml' => '<PedidoEnvioLoteRPS xmlns="http://www.prefeitura.sp.gov.br/nfe"><Cabecalho><CPFCNPJRemetente><CNPJ>00000000000000</CNPJ></CPFCNPJRemetente></Cabecalho></PedidoEnvioLoteRPS>',
    ];

    $response = $tools->emitirNfseMunicipal($payload, 'recepcionar');
    dd($response, $tools->responseHead ?? null, $tools->responseBody ?? null);
} catch (Exception $e) {
    dd($e->getMessage(), $e);
}
