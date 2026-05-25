<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderPublicaTrait
{
    /**
     * @param array|string $payload
     */
    private function buildPublicaEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapPublicaMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        $body = '<ns2:' . $method . '>'
            . '<XML>' . $this->asCdata($dados) . '</XML>'
            . '</ns2:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns2="http://service.nfse.integracao.ws.publica/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapPublicaMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'GerarNfse',
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_situacao' => 'ConsultarSituacaoLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse', 'consultar_nf_se' => 'ConsultarNfseFaixa',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            default => 'RecepcionarLoteRps',
        };
    }
}
