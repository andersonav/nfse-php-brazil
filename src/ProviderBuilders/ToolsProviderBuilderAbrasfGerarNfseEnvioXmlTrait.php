<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAbrasfGerarNfseEnvioXmlTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildAbrasfGerarNfseEnvioXml(array $data): string
    {
        $rpsXml = $this->buildAbrasfRpsXml($data);
        return '<GerarNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">' . $rpsXml . '</GerarNfseEnvio>';
    }
}
