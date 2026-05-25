<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSigCorpTrait
{
    /**
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildSigCorpEnvelope(array|string $payload, string $service, string $url, array $params): string
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $subVersao = (int) ($params['subversao'] ?? $params['SubVersao'] ?? 0);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $namespace = $this->sigCorpNamespace($data, $url);
        $method = $this->mapSigCorpMethod($normalized);

        if ($subVersao === 1) {
            $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.04'));
            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="' . $this->xmlAttr($namespace) . '">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<ws:' . $method . 'Request>'
                . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
                . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
                . '</ws:' . $method . 'Request>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<ws:' . $method . '>'
            . '<xml>' . $this->asCdata($dados) . '</xml>'
            . '</ws:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapSigCorpMethod(string $service): string
    {
        return match ($service) {
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

    /**
     * @param array<string,mixed> $data
     */
    private function sigCorpNamespace(array $data, string $url): string
    {
        $providerExtras = is_array($data['provider_extras'] ?? null) ? $data['provider_extras'] : [];
        $namespace = trim((string) ($providerExtras['namespace'] ?? ''));
        if ($namespace !== '') {
            return $namespace;
        }
        return rtrim(strtok($url, '?') ?: $url, '/');
    }

    /**
     * @param array|string $payload
     */
    private function buildSigCorpSoapAction(array|string $payload, string $service, string $url): string
    {
        $data = $this->normalizePayload($payload);
        $providerExtras = is_array($data['provider_extras'] ?? null) ? $data['provider_extras'] : [];
        $base = trim((string) ($providerExtras['soap_action'] ?? ''));
        if ($base === '') {
            $base = rtrim(strtok($url, '?') ?: $url, '/');
        }
        return rtrim($base, '#/') . '#' . $this->mapSigCorpMethod(strtolower(trim($service)));
    }
}
