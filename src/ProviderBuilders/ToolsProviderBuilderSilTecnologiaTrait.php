<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSilTecnologiaTrait
{
    /**
     * @param array|string $payload
     */
    private function buildSilTecnologiaEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $is203 = str_starts_with((string) $versao, '2.');
        $prefix = $is203 ? 'nfse' : 'ns2';
        $ns = 'http://nfse.abrasf.org.br';
        $method = $is203 ? $this->mapSilTecnologiaMethod203($service) : $this->mapSilTecnologiaMethod100($service);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:' . $prefix . '="' . $this->xmlAttr($ns) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<' . $prefix . ':' . $method . '>'
            . '<xml><![CDATA[' . $dados . ']]></xml>'
            . '</' . $prefix . ':' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }
}
