<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderTributusTrait
{
    /**
     * @param array|string $payload
     */
    private function buildTributusEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        [, $methodRequest] = $this->mapFuturizeMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml($versao ?: '2.04');

        $body = '<api:' . $methodRequest . '>'
            . '<nfseCabecMsg>' . $this->asCdata($cabecalho) . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . $this->asCdata($dados) . '</nfseDadosMsg>'
            . '</api:' . $methodRequest . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:api="https://tributosmunicipais.com.br/nfse/api/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildTributusSoapAction(string $service): string
    {
        [$method] = $this->mapFuturizeMethod($service);
        return 'https://tributosmunicipais.com.br/nfse/api/' . $method;
    }
}
