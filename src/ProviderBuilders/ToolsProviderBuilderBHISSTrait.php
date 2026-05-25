<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderBHISSTrait
{
    /**
     * Envelopes BHISS (ABRASF v1 com nfseCabecMsg/nfseDadosMsg em CDATA).
     *
     * @param array|string $payload
     */
    private function buildBHISSEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        [$requestTag] = $this->mapBHISSMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '1.00'));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.bhiss.pbh.gov.br">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<ws:' . $requestTag . '>'
            . '<nfseCabecMsg><![CDATA[' . $cabecalho . ']]></nfseCabecMsg>'
            . '<nfseDadosMsg><![CDATA[' . $dados . ']]></nfseDadosMsg>'
            . '</ws:' . $requestTag . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildBHISSSoapAction(string $service): string
    {
        [, $soapMethod] = $this->mapBHISSMethod($service);
        return 'http://ws.bhiss.pbh.gov.br/' . $soapMethod;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapBHISSMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GerarNfseRequest', 'GerarNfse'],
            'consultar_lote' => ['ConsultarLoteRpsRequest', 'ConsultarLoteRps'],
            'consultar_situacao' => ['ConsultarSituacaoLoteRpsRequest', 'ConsultarSituacaoLoteRps'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRpsRequest', 'ConsultarNfsePorRps'],
            'consultar_nfse', 'consultar_nf_se' => ['ConsultarNfsePorFaixaRequest', 'ConsultarNfse'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfseRequest', 'CancelarNfse'],
            default => ['RecepcionarLoteRpsRequest', 'RecepcionarLoteRps'],
        };
    }
}
