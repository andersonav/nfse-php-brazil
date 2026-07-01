<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderFiscoTrait
{
    /**
     * @param array|string $payload
     */
    private function buildFiscoEnvelope(array|string $payload, string $service, string $url): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapFiscoMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $namespace = rtrim(strtok($url, '?') ?: $url, '/');

        $body = '<nfse:' . $method . '>'
            . '<nfse:xml>' . $this->asCdata($dados) . '</nfse:xml>'
            . '</nfse:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope" xmlns:nfse="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapFiscoMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'gerarNfse',
            'recepcionar_sincrono' => 'recepcionarLoteRpsSincrono',
            'consultar_lote' => 'consultarLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'consultarNfsePorRps',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'consultarNfsePorFaixa',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'consultarNfseServicoPrestado',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'consultarNfseServicoTomado',
            'cancelar_nfse', 'cancelar_nf_se' => 'cancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'substituirNfse',
            default => 'recepcionarLoteRps',
        };
    }

    private function buildFiscoSoapAction(string $service, string $url): string
    {
        $base = rtrim(strtok($url, '?') ?: $url, '/') . '/';
        return $base . $this->mapFiscoMethod($service);
    }
}
