<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderPadraoNacionalTrait
{
    /**
     * @param array|string $payload
     * @return array<string,mixed>
     */
    private function buildPadraoNacionalEmitPayload(array|string $payload): array
    {
        $data = $this->normalizePayload($payload);
        if (isset($data['dpsXmlGZipB64']) && is_string($data['dpsXmlGZipB64']) && $data['dpsXmlGZipB64'] !== '') {
            return ['dpsXmlGZipB64' => $data['dpsXmlGZipB64']];
        }

        $xml = trim((string) ($data['dados_xml'] ?? $data['xml'] ?? ''));
        if ($xml === '') {
            throw new RuntimeException('PadraoNacional requer payload[dados_xml] ou payload[dpsXmlGZipB64].');
        }
        if (!str_starts_with($xml, '<?xml')) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . $xml;
        }

        $gz = gzencode($xml);
        if ($gz === false) {
            throw new RuntimeException('Falha ao compactar XML para PadraoNacional.');
        }
        return ['dpsXmlGZipB64' => base64_encode($gz)];
    }

    /**
     * @param array|string $payload
     */
    private function emitirPadraoNacional(string $url, array|string $payload): mixed
    {
        return $this->postJsonToUrl($url, $this->buildPadraoNacionalEmitPayload($payload));
    }

    /**
     * @param array|string $payload
     */
    private function cancelarPadraoNacional(string $url, array|string $payload): mixed
    {
        return $this->postJsonToUrl($url, $payload);
    }

    /**
     * @param array|string $payload
     */
    private function substituirPadraoNacional(string $url, array|string $payload): mixed
    {
        return $this->postJsonToUrl($url, $payload);
    }
}
