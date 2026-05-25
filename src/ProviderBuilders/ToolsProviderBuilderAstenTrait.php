<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAstenTrait
{
    /**
     * Envelopes Asten (ABRASF com wrapper <parameters>).
     *
     * @param array|string $payload
     */
    private function buildAstenEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapAstenMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? $versao ?? '2.02'));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfse:' . $method . '>'
            . '<parameters>'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</parameters>'
            . '</nfse:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildAstenSoapAction(string $service): string
    {
        return 'http://nfse.abrasf.org.br/' . $this->mapAstenMethod($service);
    }

    private function mapAstenMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => 'RecepcionarLoteRpsSincrono',
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'GerarNfse',
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa', 'consultar_nfse', 'consultar_nf_se' => 'ConsultarNfsePorFaixa',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'ConsultarNfseServicoPrestado',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'ConsultarNfseServicoTomado',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'SubstituirNfse',
            default => 'RecepcionarLoteRps',
        };
    }
}
