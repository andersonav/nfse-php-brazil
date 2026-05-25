<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderISSDSFTrait
{
    /**
     * Envelopes ISSDSF (operações lot:* com mensagemXml em CDATA).
     *
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildISSDSFEnvelope(array|string $payload, string $service, array $params): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapISSDSFMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $namespace = trim((string) ($params['NameSpace'] ?? $params['Namespace'] ?? $params['namespace'] ?? 'http://localhost:8080/WsNFe2/lote'));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:lot="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<lot:' . $method . '>'
            . '<mensagemXml><![CDATA[' . $dados . ']]></mensagemXml>'
            . '</lot:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapISSDSFMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => 'enviarSincrono',
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'enviarSincrono',
            'consultar_lote' => 'consultarLote',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'consultarNFSeRps',
            'consultar_seq_rps' => 'consultarSequencialRps',
            'consultar_nfse', 'consultar_nf_se' => 'consultarNota',
            'cancelar_nfse', 'cancelar_nf_se' => 'cancelar',
            default => 'enviar',
        };
    }
}
