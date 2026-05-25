<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderFintelISSTrait
{
    /**
     * Envelopes FintelISS: 2.00/2.02 com web:cabecalho+xml e 2.04 com nfseCabecMsg/nfseDadosMsg.
     *
     * @param array|string $payload
     */
    private function buildFintelISSEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapFintelISSMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $is204 = str_starts_with((string) $versao, '2.04') || str_starts_with((string) $versao, '2.4');

        if ($is204) {
            $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.04'));
            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<nfse:' . $method . '>'
                . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
                . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
                . '</nfse:' . $method . '>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? ($versao ?: '2.02')));
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="http://www.fintel.com.br/WebService">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<web:' . $method . '>'
            . '<web:cabecalho>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</web:cabecalho>'
            . '<web:xml>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</web:xml>'
            . '</web:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildFintelISSSoapAction(string $service, ?string $versao): string
    {
        $method = $this->mapFintelISSMethod($service);
        $is204 = str_starts_with((string) $versao, '2.04') || str_starts_with((string) $versao, '2.4');
        if ($is204) {
            return 'http://nfse.abrasf.org.br/' . $method;
        }
        return 'http://www.fintel.com.br/WebService/' . $method;
    }

    private function mapFintelISSMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => 'RecepcionarLoteRpsSincrono',
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'GerarNfse',
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'ConsultarNfseFaixa',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'ConsultarNfseServicoPrestado',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'ConsultarNfseServicoTomado',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'SubstituirNfse',
            default => 'RecepcionarLoteRps',
        };
    }
}
