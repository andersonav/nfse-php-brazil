<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAspecTrait
{
    /**
     * @param array|string $payload
     */
    private function buildAspecEnvelope(array|string $payload): string
    {
        $data = $this->normalizePayload($payload);
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml === '') {
            $xml = $this->buildAbrasfGerarNfseEnvioXml($data);
        }
        return $xml;
    }
}
