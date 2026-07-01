<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderISSDigitalTrait
{
    /**
     * @param array|string $payload
     */
    private function buildISSDigitalEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapISSDigitalMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.supernova.com.br/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<ws:' . $method . '>'
            . '<xml>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</xml>'
            . '</ws:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapISSDigitalMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => 'RecepcionarLoteRpsSincrono',
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'GerarNfse',
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'ConsultarNfseFaixa',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'ConsultarNfseServicoPrestado',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'ConsultarNfseServicoTomado',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'SubstituirNfse',
            default => 'RecepcionarLoteRps',
        };
    }
}
