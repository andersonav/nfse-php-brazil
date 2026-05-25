<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderGovernaTrait
{
    /**
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildGovernaEnvelope(array|string $payload, string $service, array $params): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapGovernaMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $normalized = strtolower(trim($service));
            $dados = in_array($normalized, ['cancelar_nfse', 'cancelar_nf_se'], true)
                ? $this->buildGovernaCancelXml($data, $params)
                : $this->buildGovernaEmitXml($data, $params);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<tem:' . $method . '>'
            . '<tem:pArquivoXML>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</tem:pArquivoXML>'
            . '</tem:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapGovernaMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_nfse_rps', 'consultar_nf_se_rps', 'consultar_nfse', 'consultar_nf_se' => 'RecepcionarConsultaRPS',
            'cancelar_nfse', 'cancelar_nf_se' => 'RecepcionarLoteNotasCanceladas',
            default => 'RecepcionarLoteRps',
        };
    }

    private function buildGovernaSoapAction(string $service): string
    {
        return 'http://tempuri.org/' . $this->mapGovernaMethod($service);
    }
}
