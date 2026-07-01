<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSH3Trait
{
    /**
     * @param array|string $payload
     */
    private function buildSH3Envelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        [, $methodRequest] = $this->mapFuturizeMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml($versao ?: '2.00');

        $body = '<nfse:' . $methodRequest . '>'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</nfse:' . $methodRequest . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildSH3SoapAction(string $service): string
    {
        [$method] = $this->mapFuturizeMethod($service);
        return 'http://nfse.abrasf.org.br/' . $method;
    }
}
