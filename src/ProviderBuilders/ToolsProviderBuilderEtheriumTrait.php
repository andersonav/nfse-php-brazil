<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderEtheriumTrait
{
    /**
     * Envelopes Etherium para as variantes 2.03 e 2.04.
     *
     * @param array|string $payload
     */
    private function buildEtheriumEnvelope(array|string $payload, string $service, ?string $versao, int $tpAmb): string
    {
        $data = is_array($payload) ? $payload : json_decode((string) $payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Payload invalido para Etherium.');
        }

        $method = $this->mapEtheriumMethod($service);
        $dados = $this->buildAbrasfDataForMethod($data, $method);
        $is204 = str_starts_with((string) $versao, '2.04') || str_starts_with((string) $versao, '2.4');

        if ($is204) {
            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="' . $this->xmlAttr($tpAmb === 1 ? $this->productionNamespaceFromUrl() : $this->homologNamespaceFromUrl()) . '">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<ws:' . $method . '>'
                . '<xml>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</xml>'
                . '</ws:' . $method . '>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        $cabecalho = '<cabecalho versao="' . $this->xmlAttr((string) ($versao ?: '2.03')) . '" xmlns="http://www.abrasf.org.br/nfse.xsd">'
            . '<versaoDados>' . $this->xmlValue((string) ($versao ?: '2.03')) . '</versaoDados>'
            . '</cabecalho>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<' . $method . ' xmlns="http://tempuri.org/">'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildEtheriumSoapAction(string $service, ?string $versao, string $url): string
    {
        $method = $this->mapEtheriumMethod($service);
        $is204 = str_starts_with((string) $versao, '2.04') || str_starts_with((string) $versao, '2.4');
        if ($is204) {
            $base = rtrim($url, '/');
            return $base . '#' . $method;
        }
        return 'http://tempuri.org/' . $method;
    }

    private function mapEtheriumMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        if (in_array($normalized, ['emitir_nfse', 'gerar_nfse', 'gerar_nf_se'], true)) {
            return 'GerarNfse';
        }
        if (in_array($normalized, ['recepcionar_sincrono'], true)) {
            return 'RecepcionarLoteRpsSincrono';
        }
        if (in_array($normalized, ['cancelar_nfse', 'cancelar_nf_se'], true)) {
            return 'CancelarNfse';
        }
        if (in_array($normalized, ['substituir_nfse', 'substituir_nf_se'], true)) {
            return 'SubstituirNfse';
        }
        if (in_array($normalized, ['consultar_lote'], true)) {
            return 'ConsultarLoteRps';
        }
        if (in_array($normalized, ['consultar_nfse_rps', 'consultar_nf_se_rps'], true)) {
            return 'ConsultarNfsePorRps';
        }
        return 'RecepcionarLoteRps';
    }
}
