<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderISSIntelTrait
{
    /**
     * @param array|string $payload
     */
    private function buildISSIntelEnvelope(array|string $payload, string $service, string $url): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapISSIntelMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $method);
        }
        $namespace = $this->issIntelNamespace($data, $url);

        $body = '<urn:' . $method . '>'
            . $dados
            . '</urn:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapISSIntelMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_situacao' => 'ConsultarSituacaoLoteRps',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse', 'consultar_nf_se' => 'ConsultarNfse',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            default => 'RecepcionarLoteRps',
        };
    }

    /**
     * @param array|string $payload
     */
    private function buildISSIntelSoapAction(array|string $payload, string $service, string $url): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapISSIntelMethod($service);
        $providerExtras = is_array($data['provider_extras'] ?? null) ? $data['provider_extras'] : [];
        $base = trim((string) ($providerExtras['soap_action'] ?? ''));
        if ($base === '') {
            $base = rtrim(strtok($url, '?') ?: $url, '/') . '/';
        }
        if (!str_ends_with($base, '/')) {
            $base .= '/';
        }
        return $base . $method;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function issIntelNamespace(array $data, string $url): string
    {
        $providerExtras = is_array($data['provider_extras'] ?? null) ? $data['provider_extras'] : [];
        $namespace = trim((string) ($providerExtras['namespace'] ?? ''));
        if ($namespace !== '') {
            return $namespace;
        }
        return rtrim(strtok($url, '?') ?: $url, '/');
    }
}
