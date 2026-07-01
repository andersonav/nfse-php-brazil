<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderDesenvolveTrait
{
    /**
     * @param array|string $payload
     */
    private function buildDesenvolveEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapDesenvolveMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="http://ws.integracao.nfsd.desenvolve/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<tns:' . $method . '>'
            . '<xml>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</xml>'
            . '</tns:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapDesenvolveMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => 'enviarLoteRpsSincronoEnvio',
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'gerarNfseEnvio',
            'consultar_lote' => 'consultarLoteRpsEnvio',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'consultarNfseRpsEnvio',
            'cancelar_nfse', 'cancelar_nf_se' => 'cancelarNfseEnvio',
            default => 'enviarLoteRpsEnvio',
        };
    }
}
