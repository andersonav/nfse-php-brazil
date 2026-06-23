<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderMegaSoftTrait
{
    /**
     * @param array|string $payload
     */
    private function buildMegaSoftEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        [$method, $methodRequest] = $this->mapMegaSoftMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml('1.00');

        $body = '<ws:' . $methodRequest . '>'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</ws:' . $methodRequest . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.megasoftarrecadanet.com.br">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapMegaSoftMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRps', 'ConsultarNfsePorRpsRequest'],
            default => ['GerarNfse', 'GerarNfseRequest'],
        };
    }

    private function buildMegaSoftSoapAction(string $service): string
    {
        [$method] = $this->mapMegaSoftMethod($service);
        return 'http://ws.megasoftarrecadanet.com.br/' . $method;
    }
}
