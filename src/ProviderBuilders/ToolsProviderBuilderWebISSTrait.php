<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderWebISSTrait
{
    /**
     * @param array|string $payload
     */
    private function buildWebISSEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml($versao ?: '2.02');
        $normalized = strtolower(trim($service));

        $is202 = str_starts_with((string) $versao, '2.');
        if ($is202) {
            [, $methodRequest] = $this->mapFuturizeMethod($service);
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

        [$method] = $this->mapWebISSLegacyMethod($normalized);
        $body = '<' . $method . ' xmlns="http://tempuri.org/">'
            . '<cabec>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</cabec>'
            . '<msg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</msg>'
            . '</' . $method . '>';
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildWebISSSoapAction(string $service, ?string $versao): string
    {
        $normalized = strtolower(trim($service));
        $is202 = str_starts_with((string) $versao, '2.');
        if ($is202) {
            [$method] = $this->mapFuturizeMethod($service);
            return 'http://nfse.abrasf.org.br/' . $method;
        }
        [$method, $path] = $this->mapWebISSLegacyMethod($normalized);
        return 'http://tempuri.org/INfseServices/' . $path;
    }
}
