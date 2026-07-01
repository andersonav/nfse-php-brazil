<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAbacoTrait
{
    /**
     * @param array|string $payload
     */
    private function buildAbacoEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapAbacoMethod($service, $versao);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? ($versao ?: '2.04')));
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:e="http://www.e-nfs.com.br">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<e:' . $method . '.Execute>'
            . '<e:Nfsecabecmsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</e:Nfsecabecmsg>'
            . '<e:Nfsedadosmsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</e:Nfsedadosmsg>'
            . '</e:' . $method . '.Execute>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapAbacoMethod(string $service, ?string $versao): string
    {
        $normalized = strtolower(trim($service));
        $prefix = str_starts_with((string) $versao, '2.') ? 'A24_' : '';
        $base = match ($normalized) {
            'consultar_situacao' => 'ConsultarSituacaoLoteRps',
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'ConsultarNfsePorFaixa',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'ConsultarNfseServicoPrestado',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'ConsultarNfseServicoTomado',
            'consultar_nfse', 'consultar_nf_se' => 'ConsultarNfse',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'SubstituirNfse',
            default => 'RecepcionarLoteRPS',
        };
        return $prefix . $base;
    }

    private function buildAbacoSoapAction(string $service, ?string $versao): string
    {
        $method = strtoupper($this->mapAbacoMethod($service, $versao));
        return 'http://www.e-nfs.com.braction/A' . $method . '.Execute';
    }
}
