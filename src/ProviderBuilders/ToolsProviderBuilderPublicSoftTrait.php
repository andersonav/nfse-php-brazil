<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderPublicSoftTrait
{
    /**
     * @param array|string $payload
     */
    private function buildPublicSoftEnvelope(array|string $payload): string
    {
        $data = $this->normalizePayload($payload);
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml === '') {
            $xml = $this->buildAbrasfDataForMethod($data, 'recepcionar');
        }
        if (!str_starts_with($xml, '<?xml')) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . $xml;
        }
        return $xml;
    }

    /**
     * @param array|string $payload
     * @return array<int,string>
     */
    private function buildPublicSoftHeaders(array|string $payload, int $tpAmb): array
    {
        $data = $this->normalizePayload($payload);
        $auth = $this->payloadAuth($data);
        $codigoCidade = trim((string) ($auth['codigo_cidade'] ?? $auth['cidade_codigo'] ?? $auth['codigoCidade'] ?? ''));
        $token = trim((string) ($auth['token'] ?? $auth['ws_chave_acesso'] ?? ''));
        if ($codigoCidade === '' || $token === '') {
            throw new RuntimeException('PublicSoft requer auth.codigo_cidade e auth.token.');
        }

        $headers = [
            'codigoCidade: ' . $codigoCidade,
            'token: ' . $token,
        ];
        if ($tpAmb === 1) {
            $headers[] = 'producao: true';
        }
        return $headers;
    }
}
