<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderEquiplanoTrait
{
    /**
     * @param array|string $payload
     */
    private function buildEquiplanoEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapEquiplanoMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $nrVersaoXml = trim((string) ($data['versao_dados'] ?? $versao ?? '1'));

        $body = '<ser:' . $method . '>'
            . '<ser:nrVersaoXml>' . $this->xmlValue($nrVersaoXml) . '</ser:nrVersaoXml>'
            . '<ser:xml>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</ser:xml>'
            . '</ser:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://services.enfsws.es">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapEquiplanoMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_situacao' => 'esConsultarSituacaoLoteRps',
            'consultar_lote' => 'esConsultarLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'esConsultarNfsePorRps',
            'consultar_nfse', 'consultar_nf_se' => 'esConsultarNfse',
            'cancelar_nfse', 'cancelar_nf_se' => 'esCancelarNfse',
            default => 'esRecepcionarLoteRps',
        };
    }

    private function buildEquiplanoSoapAction(string $service): string
    {
        return 'urn:' . $this->mapEquiplanoMethod($service);
    }
}
