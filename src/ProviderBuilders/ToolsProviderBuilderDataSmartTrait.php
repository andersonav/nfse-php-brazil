<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderDataSmartTrait
{
    /**
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildDataSmartEnvelope(array|string $payload, string $service, int $tpAmb, array $params): string
    {
        $data = $this->normalizePayload($payload);
        [$method, $tagEnvio] = $this->mapDataSmartMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.02'));
        $auth = $this->payloadAuth($data);
        $username = trim((string) ($auth['username'] ?? $auth['ws_user'] ?? ''));
        $password = trim((string) ($auth['password'] ?? $auth['ws_senha'] ?? ''));
        $prefeitura = trim((string) ($auth['prefeitura'] ?? ''));
        if ($prefeitura === '') {
            $prefeitura = $tpAmb === 1
                ? (string) ($params['aliascidade'] ?? $params['AliasCidade'] ?? '')
                : 'BANCO_DEMONSTRACAO';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:dat="http://www.datasmart.com.br/" xmlns:nfse="http://www.abrasf.org.br/nfse.xsd">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<dat:' . $method . '><parameters href="#1"/></dat:' . $method . '>'
            . '<nfse:' . $tagEnvio . ' id="1">'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '<Username>' . $this->xmlValue($username) . '</Username>'
            . '<Password>' . $this->xmlValue($password) . '</Password>'
            . '<Prefeitura>' . $this->xmlValue($prefeitura) . '</Prefeitura>'
            . '</nfse:' . $tagEnvio . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapDataSmartMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => ['ConsultarNfseFaixa', 'ConsultarNfseFaixaEnvio'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRps', 'ConsultarNfsePorRpsEnvio'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse', 'CancelarNfseEnvio'],
            default => ['GerarNfse', 'GerarNfseEnvio'],
        };
    }

    private function buildDataSmartSoapAction(string $service): string
    {
        [$method] = $this->mapDataSmartMethod($service);
        return 'http://www.datasmart.com.br/' . $method;
    }
}
