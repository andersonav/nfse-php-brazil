<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderTiplanClassicTrait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildTiplanClassicEnvelope(array $data, string $service): string
    {
        $method = $this->mapTiplanClassicMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<' . $method . 'Request xmlns="http://www.nfe.com.br/">'
            . '<inputXML>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</inputXML>'
            . '</' . $method . 'Request>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapTiplanClassicMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_situacao' => 'ConsultarSituacaoLoteRps',
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse', 'consultar_nf_se' => 'ConsultarNfse',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            default => 'RecepcionarLoteRps',
        };
    }
}
