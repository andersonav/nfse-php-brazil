<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCTAEmitXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildCTAEmitXml(array $data): string
    {
        $auth = $this->payloadAuth($data);
        $chave = trim((string) ($auth['ws_chave_acesso'] ?? $auth['token'] ?? ''));
        $xmlNfse = trim((string) ($data['nfse_xml'] ?? ''));
        if ($xmlNfse === '') {
            $xmlNfse = $this->buildAbrasfGerarNfseEnvioXml($data);
        }
        return '<Informacoes><chave_acesso>' . $this->xmlValue($chave) . '</chave_acesso>'
            . '<xml_nfse><![CDATA[' . $xmlNfse . ']]></xml_nfse></Informacoes>';
    }
}
