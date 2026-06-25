<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderWebFiscoTrait
{
    /**
     * Envelopes WebFisco (requisição XML direta nos endpoints SOAPAction específicos).
     *
     * @param array|string $payload
     */
    private function buildWebFiscoEnvelope(array|string $payload, string $service, int $tpAmb): string
    {
        $data = $this->normalizePayload($payload);
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml !== '') {
            return $xml;
        }
        if (in_array(strtolower(trim($service)), ['cancelar_nfse', 'cancelar_nf_se'], true)) {
            return $this->buildWebFiscoCancelXml($data);
        }
        return $this->buildWebFiscoEmitXml($data, $tpAmb);
    }

    private function buildWebFiscoSoapAction(string $service, int $tpAmb): string
    {
        $base = 'https://www.webfiscotecnologia.com.br/issqn/wservice/';
        $normalized = strtolower(trim($service));
        if (in_array($normalized, ['cancelar_nfse', 'cancelar_nf_se'], true)) {
            return $base . 'wsnfecancela.php/CancelaNfe';
        }
        if (in_array($normalized, ['consultar_nfse', 'consultar_nf_se', 'consultar_nfse_rps', 'consultar_nf_se_rps'], true)) {
            return $base . 'wsnfeconsultaxml.php/ConsultaNfe';
        }
        return $base . ($tpAmb === 1 ? 'wsnfeenvia.php/EnvNfe' : 'wsnfe_teste_homologacao.php/EnvNfe');
    }
}
