<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSigepTrait
{
    /**
     * Envelopes Sigep (BSIT) no formato esperado pelo provedor.
     *
     * @param array|string $payload
     */
    private function buildSigepEnvelope(array|string $payload, string $service): string
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Payload invalido para Sigep.');
        }

        $normalized = strtolower(trim($service));
        if ($normalized === 'recepcionar') {
            $normalized = 'recepcionar_sincrono';
        }
        $methodMap = [
            'recepcionar_sincrono' => ['enviarLoteRpsSincrono', 'EnviarLoteRpsSincronoEnvio'],
            'emitir_nfse' => ['gerarNfse', 'GerarNfseEnvio'],
            'gerar_nfse' => ['gerarNfse', 'GerarNfseEnvio'],
            'gerar_nf_se' => ['gerarNfse', 'GerarNfseEnvio'],
            'consultar_lote' => ['consultarLoteRps', 'ConsultarLoteRpsEnvio'],
            'consultar_nfse_rps' => ['consultarNfseRps', 'ConsultarNfseRpsEnvio'],
            'consultar_nf_se_rps' => ['consultarNfseRps', 'ConsultarNfseRpsEnvio'],
            'cancelar_nfse' => ['cancelarNfse', 'CancelarNfseEnvio'],
            'cancelar_nf_se' => ['cancelarNfse', 'CancelarNfseEnvio'],
        ];
        if (!isset($methodMap[$normalized])) {
            throw new RuntimeException("Servico '{$service}' nao suportado para Sigep no emissor municipal.");
        }
        [$wsMethod, $envTag] = $methodMap[$normalized];
        $dados = $this->buildAbrasfDataForMethod($data, $wsMethod);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.integration.pm.bsit.com.br/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<ws:' . $wsMethod . '>'
            . '<' . $envTag . '>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</' . $envTag . '>'
            . '</ws:' . $wsMethod . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }
}
