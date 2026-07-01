<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderGeisWebTrait
{
    /**
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildGeisWebEnvelope(array|string $payload, string $service, array $params): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapGeisWebMethod($service);
        $arg = $this->mapGeisWebArg($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:geis="urn:geisweb" xmlns:ns1="urn:WsInterfaseNfse">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<geis:' . $method . ' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">'
            . '<' . $arg . ' xsi:type="xsd:string" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
            . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            . '</' . $arg . '>'
            . '</geis:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapGeisWebMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => 'ConsultaLoteRps',
            'consultar_nfse', 'consultar_nf_se', 'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultaNfse',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelaNfse',
            default => 'EnviaLoteRps',
        };
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildGeisWebSoapAction(string $service, array $params): string
    {
        $aliasCidade = (string) ($params['AliasCidade'] ?? $params['aliascidade'] ?? 'Cidade');
        return 'urn:WsInterfaseNfse-IwsInterfaseNfse#' . $aliasCidade . $this->mapGeisWebMethod($service);
    }
}
