<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderBauhausTrait
{
    /**
     * @param array|string $payload
     */
    private function buildBauhausEnvelope(array|string $payload): string
    {
        $data = $this->normalizePayload($payload);
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml === '') {
            $xml = $this->buildAbrasfDataForMethod($data, 'recepcionar');
        }
        return $xml;
    }

    /**
     * @param array|string $payload
     * @return array<int,string>
     */
    private function buildBauhausHeaders(array|string $payload): array
    {
        $data = $this->normalizePayload($payload);
        $auth = $this->payloadAuth($data);
        $authorization = trim((string) ($auth['authorization'] ?? $auth['token'] ?? ''));
        if ($authorization === '') {
            throw new RuntimeException('Bauhaus requer auth.authorization (ou auth.token).');
        }
        return ['Authorization: ' . $authorization];
    }
}
