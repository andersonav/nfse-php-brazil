<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderDeISSTrait
{
    /**
     * Envelopes DeISS (tns:*Request com namespace/soapaction configuráveis).
     *
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildDeISSEnvelope(array|string $payload, string $service, ?string $versao, array $params, string $url): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapDeISSMethod($service);
        $namespace = $this->resolveDeISSNamespace($params, $url);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? $versao ?? '2.03'));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tns="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<tns:' . $method . 'Request>'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</tns:' . $method . 'Request>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildDeISSSoapAction(string $service, array $params, string $url): string
    {
        $base = trim((string) ($params['SoapAction'] ?? $params['soap_action'] ?? ''));
        if ($base === '') {
            $base = rtrim(strtok($url, '?') ?: $url, '/');
            $base .= '/';
        }
        if (!str_ends_with($base, '/')) {
            $base .= '/';
        }
        return $base . $this->mapDeISSMethod($service);
    }

    private function mapDeISSMethod(string $service): string
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

    private function productionNamespaceFromUrl(): string
    {
        return 'http://tempuri.org/';
    }

    private function homologNamespaceFromUrl(): string
    {
        return 'http://tempuri.org/';
    }
}
