<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderMetropolisWebTrait
{
    /**
     * Envelopes MetropolisWeb (end:* com inner *Request).
     *
     * @param array|string $payload
     */
    private function buildMetropolisWebEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        [$method, $requestTag] = $this->mapMetropolisWebMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '1.00'));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:end="http://endpoint.nfse.ws.webservicenfse.edza.com.br/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<end:' . $method . '>'
            . '<' . $requestTag . '>'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</' . $requestTag . '>'
            . '</end:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapMetropolisWebMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => ['ConsultarLoteRps', 'ConsultarLoteRpsRequest'],
            'consultar_situacao' => ['ConsultarSituacaoLoteRps', 'ConsultarSituacaoLoteRpsRequest'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRps', 'ConsultarNfsePorRpsRequest'],
            'consultar_nfse', 'consultar_nf_se' => ['ConsultarNfse', 'ConsultarNfseRequest'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse', 'CancelarNfseRequest'],
            default => ['RecepcionarLoteRps', 'RecepcionarLoteRpsRequest'],
        };
    }
}
