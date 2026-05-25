<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderISSLegalTrait
{
    /**
     * @param array|string $payload
     * @return array<int,string>
     */
    private function buildISSLegalHeaders(array|string $payload): array
    {
        $data = $this->normalizePayload($payload);
        $auth = $this->payloadAuth($data);
        $authorization = trim((string) ($auth['ws_chave_autorizacao'] ?? $auth['authorization'] ?? $auth['token'] ?? ''));
        if ($authorization === '') {
            throw new RuntimeException('ISSLegal requer auth.ws_chave_autorizacao (ou auth.authorization/auth.token).');
        }
        return ['Authorization: ' . $authorization];
    }
}
