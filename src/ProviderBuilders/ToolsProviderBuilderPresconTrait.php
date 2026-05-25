<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderPresconTrait
{
    /**
     * @param array|string $payload
     */
    private function buildPresconEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $xml = trim((string) ($data['dados_xml'] ?? ''));
        if ($xml !== '') {
            return $xml;
        }
        $normalized = strtolower(trim($service));
        $auth = $this->payloadAuth($data);
        $token = trim((string) ($auth['ws_chave_autorizacao'] ?? $auth['token'] ?? ''));
        if (in_array($normalized, ['cancelar_nfse', 'cancelar_nf_se'], true)) {
            $numero = trim((string) ($data['numero_nfse'] ?? $data['numero'] ?? ''));
            $motivo = trim((string) ($data['motivo'] ?? ''));
            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
                . '<soapenv:Header/><soapenv:Body>'
                . '<setCancelNfeOnly soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
                . '<strIdNfse xsi:type="xsd:int">' . $this->xmlValue($numero) . '</strIdNfse>'
                . '<strReason xsi:type="xsd:string">' . $this->xmlValue($motivo) . '</strReason>'
                . '<strToken xsi:type="xsd:string">' . $this->xmlValue($token) . '</strToken>'
                . '</setCancelNfeOnly>'
                . '</soapenv:Body></soapenv:Envelope>';
        }
        $json = trim((string) ($data['dados_json'] ?? ''));
        if ($json === '') {
            $json = is_string($payload) ? $payload : json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
            . '<soapenv:Header/><soapenv:Body>'
            . '<setInvoice soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
            . '<strJsonInvoice xsi:type="xsd:string">' . htmlspecialchars((string) $json, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</strJsonInvoice>'
            . '<strToken xsi:type="xsd:string">' . $this->xmlValue($token) . '</strToken>'
            . '</setInvoice>'
            . '</soapenv:Body></soapenv:Envelope>';
    }
}
