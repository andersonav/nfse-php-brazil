<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCTATrait
{
    /**
     * Requisições CTA (payload XML direto).
     *
     * @param array|string $payload
     */
    private function buildCTAEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml !== '') {
            return $xml;
        }
        $normalized = strtolower(trim($service));
        if (in_array($normalized, ['cancelar_nfse', 'cancelar_nf_se'], true)) {
            return $this->buildCTACancelXml($data);
        }
        if (in_array($normalized, ['substituir_nfse', 'substituir_nf_se'], true)) {
            return $this->buildAbrasfSubstituirNfseEnvioXml($data);
        }
        return $this->buildCTAEmitXml($data);
    }
}
