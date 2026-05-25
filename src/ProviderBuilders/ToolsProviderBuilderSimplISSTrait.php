<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSimplISSTrait
{
    /**
     * @param array|string $payload
     */
    private function buildSimplISSEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $is203 = str_starts_with((string) $versao, '2.');

        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        if ($is203) {
            [$method, $requestTag] = $this->mapSimplISS203Method($normalized);
            $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.03'));
            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<nfse:' . $requestTag . '>'
                . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
                . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
                . '</nfse:' . $requestTag . '>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        [$methodLegacy] = $this->mapSimplISSLegacyMethod($normalized);
        $auth = $this->payloadAuth($data);
        $wsUser = trim((string) ($auth['username'] ?? $auth['ws_user'] ?? ''));
        $wsPass = trim((string) ($auth['password'] ?? $auth['ws_senha'] ?? ''));
        if ($wsUser === '' || $wsPass === '') {
            throw new RuntimeException('SimplISS legado requer auth.username e auth.password.');
        }
        $param = '<sis:pParam>'
            . '<sis1:P1>' . $this->xmlValue($wsUser) . '</sis1:P1>'
            . '<sis1:P2>' . $this->xmlValue($wsPass) . '</sis1:P2>'
            . '</sis:pParam>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sis="http://www.sistema.com.br/Sistema.Ws.Nfse" xmlns:sis1="http://www.sistema.com.br/Sistema.Ws.Nfse.Cn" xmlns:nfse="http://www.sistema.com.br/Nfse/arquivos/nfse_3.xsd">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<sis:' . $methodLegacy . '>'
            . $dados
            . $param
            . '</sis:' . $methodLegacy . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildSimplISSSoapAction(string $service, ?string $versao): string
    {
        $normalized = strtolower(trim($service));
        if (str_starts_with((string) $versao, '2.')) {
            [$method] = $this->mapSimplISS203Method($normalized);
            return 'http://nfse.abrasf.org.br/INfseService/' . $method;
        }
        [$method] = $this->mapSimplISSLegacyMethod($normalized);
        return 'http://www.sistema.com.br/Sistema.Ws.Nfse/INfseService/' . $method;
    }
}
