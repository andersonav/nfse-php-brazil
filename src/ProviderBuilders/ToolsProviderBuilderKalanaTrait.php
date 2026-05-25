<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderKalanaTrait
{
    /**
     * @param array|string $payload
     */
    private function buildKalanaEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $auth = $this->payloadAuth($data);
        $chave = trim((string) ($auth['chave'] ?? $auth['ws_chave_acesso'] ?? ''));

        if (in_array($normalized, ['consultar_nfse', 'consultar_nf_se', 'cancelar_nfse', 'cancelar_nf_se'], true)) {
            $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.00'));
            $method = in_array($normalized, ['cancelar_nfse', 'cancelar_nf_se'], true) ? 'CancelarNfseRequest' : 'ConsultarNfseRequest';
            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsn="https://www.kalana.com.br/wsnfe">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<wsn:' . $method . '>'
                . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
                . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
                . '</wsn:' . $method . '>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        $method = match ($normalized) {
            'consultar_lote' => 'ConsultarLoteRpsEnvio',
            'consultar_situacao' => 'ConsultarSituacaoLoteRpsEnvio',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRpsEnvio',
            default => 'EnviarLoteRpsEnvio',
        };
        $innerTag = match ($normalized) {
            'consultar_lote' => 'ConsultarLoteRpsEnvio',
            'consultar_situacao' => 'ConsultarSituacaoLoteRpsEnvio',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfseRpsEnvio',
            default => 'EnviarLoteRpsEnvio',
        };
        $inner = $this->extractInnerXmlByTagName($dados, $innerTag);

        $xml = '<wsn:' . $method . '>';
        if ($chave !== '' && $normalized !== 'consultar_situacao') {
            $xml .= '<Chave>' . $this->xmlValue($chave) . '</Chave>';
        }
        $xml .= $inner;
        if ($chave !== '' && $normalized === 'consultar_situacao') {
            $xml .= '<Chave>' . $this->xmlValue($chave) . '</Chave>';
        }
        $xml .= '</wsn:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsn="https://www.kalana.com.br/wsnfe">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $xml . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }
}
