<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSinteseTrait
{
    /**
     * @param array|string $payload
     */
    private function buildSinteseEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        [, $methodRequest] = $this->mapFuturizeMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml($versao ?: '2.04');

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

    private function buildSinteseSoapAction(string $service, string $url): string
    {
        [$method] = $this->mapFuturizeMethod($service);
        $base = rtrim((string) preg_replace('#\\?.*$#', '', $url), '/');
        return $base !== '' ? $base . '/' . $method : ('http://nfse.abrasf.org.br/' . $method);
    }
}
