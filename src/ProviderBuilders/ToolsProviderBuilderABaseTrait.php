<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderABaseTrait
{
    /**
     * @param array|string $payload
     */
    private function buildABaseEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapABaseMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.01'));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfs="http://nfse.abase.com.br/NFSeWS">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfs:' . $method . '>'
            . '<nfs:nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfs:nfseCabecMsg>'
            . '<nfs:nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfs:nfseDadosMsg>'
            . '</nfs:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapABaseMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => 'ConsultaLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultaNfseRps',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelaNfse',
            default => 'RecepcionarLoteRps',
        };
    }

    private function buildABaseSoapAction(string $service): string
    {
        return 'http://nfse.abase.com.br/NFSeWS/' . $this->mapABaseMethod($service);
    }
}
