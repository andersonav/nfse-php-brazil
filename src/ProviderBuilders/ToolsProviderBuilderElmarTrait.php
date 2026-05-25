<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderElmarTrait
{
    /**
     * @param array|string $payload
     */
    private function buildElmarEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        // Em parte dos ambientes Elmar o corpo segue direto para Gerar/Recepcionar.
        if (in_array($normalized, ['recepcionar', 'gerar_nfse', 'gerar_nf_se', 'emitir_nfse'], true)) {
            return $dados;
        }

        [, $methodRequest] = $this->mapFuturizeMethod($service);
        $cabecalho = $this->buildAbrasfCabecalhoXml($versao ?: '2.02');
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

    private function buildElmarSoapAction(string $service): string
    {
        [$method] = $this->mapFuturizeMethod($service);
        return 'http://nfse.abrasf.org.br/' . $method;
    }
}
