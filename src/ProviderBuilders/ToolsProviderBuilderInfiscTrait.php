<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderInfiscTrait
{
    /**
     * @param array|string $payload
     */
    private function buildInfiscEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapInfiscMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $method);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.03'));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<' . $method . ' xmlns="http://nfse.abrasf.org.br">'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapInfiscMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'GerarNfse',
            'recepcionar_sincrono' => 'RecepcionarLoteRpsSincrono',
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'ConsultarNfseFaixa',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'ConsultarNfseServicoPrestado',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'ConsultarNfseServicoTomado',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'SubstituirNfse',
            default => 'RecepcionarLoteRps',
        };
    }
}
