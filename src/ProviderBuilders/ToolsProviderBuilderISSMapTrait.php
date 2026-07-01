<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderISSMapTrait
{
    /**
     * @param array|string $payload
     */
    private function buildISSMapEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml === '') {
            $xml = $this->buildAbrasfDataForMethod($data, $service);
        }
        return $xml;
    }
}
