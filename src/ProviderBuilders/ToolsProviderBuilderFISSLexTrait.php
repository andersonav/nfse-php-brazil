<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderFISSLexTrait
{
    /**
     * @param array|string $payload
     */
    private function buildFISSLexEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapFISSLexMethod($service);
        $dataTag = $this->mapFISSLexDataTag($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '1.00'));

        $content = in_array(strtolower(trim($service)), ['recepcionar', 'cancelar_nfse', 'cancelar_nf_se'], true)
            ? htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            : $dados;

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:fiss="FISS-LEX">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<fiss:' . $method . '.Execute>'
            . (str_starts_with($method, 'WS_Recepcionar') || str_starts_with($method, 'WS_Cancelar') ? '' : '<fiss:nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</fiss:nfseCabecMsg>')
            . '<fiss:' . $dataTag . '>' . $content . '</fiss:' . $dataTag . '>'
            . '</fiss:' . $method . '.Execute>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapFISSLexMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => 'WS_ConsultaLoteRps',
            'consultar_situacao' => 'WS_ConsultarSituacaoLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'WS_ConsultaNfsePorRps',
            'consultar_nfse', 'consultar_nf_se' => 'WS_ConsultaNfse',
            'cancelar_nfse', 'cancelar_nf_se' => 'WS_CancelarNfse',
            default => 'WS_RecepcionarLoteRps',
        };
    }

    private function mapFISSLexDataTag(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => 'Consultarloterpsenvio',
            'consultar_situacao' => 'Consultarsituacaoloterpsenvio',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'Consultarnfserpsenvio',
            'consultar_nfse', 'consultar_nf_se' => 'Consultarnfseenvio',
            'cancelar_nfse', 'cancelar_nf_se' => 'Cancelarnfseenvio',
            default => 'Enviarloterpsenvio',
        };
    }

    private function buildFISSLexSoapAction(string $service): string
    {
        $method = strtoupper($this->mapFISSLexMethod($service));
        return 'FISS-LEXaction/A' . $method . '.Execute';
    }
}
