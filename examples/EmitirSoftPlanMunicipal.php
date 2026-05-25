<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Sao_Paulo');
include __DIR__ . '/../vendor/autoload.php';

try {
    $config = new stdClass();
    $config->tpamb = 2; // 1 - producao, 2 - homologacao
    $config->prefeitura = '4205407'; // Florianopolis/SC (SoftPlan)
    $config->catalog_compiled_path = __DIR__ . '/../storage/municipios-acbr.php';

    $configJson = json_encode($config);
    $content = file_get_contents('certificado.pfx');
    $password = 'senha_certificado';
    $cert = \NFePHP\Common\Certificate::readPfx($content, $password);
    $tools = new \Alves\NfseBrasil\Tools($configJson, $cert);

    $payload = [
        'auth' => [
            'username' => 'SEU_USUARIO',
            'password' => 'SUA_SENHA',
            'client_id' => 'SEU_CLIENT_ID',
            'client_secret' => 'SEU_CLIENT_SECRET',
            // opcional para evitar chamada de token:
            // 'access_token' => 'SEU_TOKEN_JA_GERADO',
        ],
        'lote' => [
            'numero_lote' => '1',
        ],
        'rps' => [[
            'identificacao' => [
                'numero' => '1',
                'serie' => 'UNICA',
                'tipo' => 1,
            ],
            'data_emissao' => date('Y-m-d\TH:i:s'),
            'prestador' => [
                'cnpj' => '00000000000000',
                'inscricao_municipal' => '123456',
            ],
            'tomador' => [
                'documento' => '00000000000',
                'nome_razao_social' => 'Tomador Homologacao',
                'email' => 'tomador@example.com',
            ],
            'servico' => [
                'codigo_cnae' => '0000000',
                'item_lista_servico' => '0107',
                'discriminacao' => 'Servico de homologacao',
                'valor_servicos' => 100.00,
                'aliquota' => 0.02,
                'iss_retido' => 2,
                'codigo_municipio' => '4205407',
            ],
        ]],
    ];

    $response = $tools->emitirNfseMunicipal($payload, 'recepcionar');
    dd($response, $tools->responseHead ?? null, $tools->responseBody ?? null);
} catch (Exception $e) {
    dd($e->getMessage(), $e);
}
