<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderCIGATrait
{
    /**
     * @param array|string $payload
     */
    private function buildCIGAEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        [$method, $requestTag] = $this->mapCIGAMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $method);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml($versao ?: '1.00');

        $body = '<nfse:' . $requestTag . '>'
            . '<nfseCabecMsg><![CDATA[' . $cabecalho . ']]></nfseCabecMsg>'
            . '<nfseDadosMsg><![CDATA[' . $dados . ']]></nfseDadosMsg>'
            . '</nfse:' . $requestTag . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapCIGAMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_situacao' => ['ConsultarSituacaoLoteRps', 'ConsultarSituacaoLoteRpsRequest'],
            'consultar_lote' => ['ConsultarLoteRps', 'ConsultarLoteRpsRequest'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRps', 'ConsultarNfseRpsRequest'],
            'consultar_nfse', 'consultar_nf_se' => ['ConsultarNfse', 'ConsultarNfseRequest'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse', 'CancelarNfseRequest'],
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['RecepcionarLoteRps', 'RecepcionarLoteRpsRequest'],
            default => ['RecepcionarLoteRps', 'RecepcionarLoteRpsRequest'],
        };
    }

    private function buildCIGASoapAction(string $service): string
    {
        [$method] = $this->mapCIGAMethod($service);
        return 'http://nfse.abrasf.org.br/' . $method;
    }
}
