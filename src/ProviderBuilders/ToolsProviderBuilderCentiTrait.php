<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCentiTrait
{
    /**
     * @param array|string $payload
     */
    private function buildCentiEnvelope(array|string $payload): string
    {
        $data = $this->normalizePayload($payload);
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml === '') {
            $xml = $this->buildAbrasfDataForMethod($data, 'gerar_nfse');
        }
        return $xml;
    }

    private function buildCentiSoapAction(string $service, int $tpAmb): string
    {
        $suffix = $tpAmb === 2 ? 'Homologacao' : '';
        $normalized = strtolower(trim($service));
        $method = match ($normalized) {
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfseRps',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            default => 'GerarNfse',
        };
        return 'http://tempuri.org/IServiceNfse/' . $method . $suffix;
    }
}
