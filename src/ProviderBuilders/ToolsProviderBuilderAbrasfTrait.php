<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAbrasfTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildAbrasfDataForMethod(array $data, string $methodOrTag): string
    {
        $method = strtolower($methodOrTag);
        if (str_contains($method, 'cancelar')) {
            return $this->buildAbrasfCancelarNfseEnvioXml($data);
        }
        if (str_contains($method, 'substituir')) {
            return $this->buildAbrasfSubstituirNfseEnvioXml($data);
        }
        if (str_contains($method, 'gerar')) {
            return $this->buildAbrasfGerarNfseEnvioXml($data);
        }

        return $this->buildAbrasfEnviarLoteRpsEnvioXml($data);
    }
}
