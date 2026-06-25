<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderWebFiscoEmitXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildWebFiscoEmitXml(array $data, int $tpAmb): string
    {
        $auth = $this->payloadAuth($data);
        $usuario = trim((string) ($auth['usuario'] ?? $auth['username'] ?? ''));
        $senha = trim((string) ($auth['senha'] ?? $auth['password'] ?? ''));
        $cnpjPrefeitura = trim((string) ($data['cnpj_prefeitura'] ?? $auth['cnpj_prefeitura'] ?? ''));
        $cnpjPrestador = preg_replace('/\D+/', '', (string) ($data['prestador_cnpj'] ?? ($data['rps'][0]['prestador']['cnpj'] ?? '')));
        $xmlNfse = trim((string) ($data['nfse_xml'] ?? ''));
        if ($xmlNfse === '') {
            $xmlNfse = $this->buildAbrasfDataForMethod($data, 'gerar_nfse');
        }
        $producao = $tpAmb === 1 ? '1' : '2';

        return '<EnvNfe>'
            . '<usuario xsi:type="xsd:string">' . $this->xmlValue($usuario) . '</usuario>'
            . '<pass xsi:type="xsd:string">' . $this->xmlValue($senha) . '</pass>'
            . '<prf xsi:type="xsd:string">' . $this->xmlValue($cnpjPrefeitura) . '</prf>'
            . '<usr xsi:type="xsd:string">' . $this->xmlValue($cnpjPrestador) . '</usr>'
            . '<xml xsi:type="xsd:string">' . htmlspecialchars($xmlNfse, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</xml>'
            . '<producao xsi:type="xsd:string">' . $this->xmlValue($producao) . '</producao>'
            . '</EnvNfe>';
    }
}
