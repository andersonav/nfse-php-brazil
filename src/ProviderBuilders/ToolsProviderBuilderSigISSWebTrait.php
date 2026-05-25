<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSigISSWebTrait
{
    /**
     * @param array|string $payload
     */
    private function buildSigISSWebEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml !== '') {
            return $xml;
        }
        return $this->buildAbrasfDataForMethod($data, $service);
    }

    /**
     * @param array|string $payload
     * @return array<int,string>
     */
    private function buildSigISSWebHeaders(array|string $payload): array
    {
        $data = $this->normalizePayload($payload);
        $auth = $this->payloadAuth($data);
        $authorization = trim((string) ($auth['authorization'] ?? $auth['token'] ?? ''));
        if ($authorization === '') {
            throw new RuntimeException('SigISSWeb requer auth.token (ou auth.authorization).');
        }
        return ['Authorization: ' . $authorization];
    }
}
