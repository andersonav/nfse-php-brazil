<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderTinusTrait
{
    /**
     * @param array|string $payload
     */
    private function buildTinusEnvelope(array|string $payload, string $service, ?string $versao, int $tpAmb): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapTinusMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $method);
        }
        $namespace = $this->tinusNamespace($versao, $tpAmb);

        $body = '<tin:' . $method . '>'
            . $dados
            . '</tin:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tin="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapTinusMethod(string $service): string
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

    private function tinusNamespace(?string $versao, int $tpAmb): string
    {
        if (str_starts_with((string) $versao, '1.02')) {
            return 'http://www.abrasf.org.br/nfse.xsd';
        }
        return $tpAmb === 1 ? 'http://www.tinus.com.br' : 'http://www2.tinus.com.br';
    }

    private function buildTinusSoapAction(string $service, ?string $versao): string
    {
        $base = str_starts_with((string) $versao, '1.02')
            ? 'http://www.abrasf.org.br/nfse.xsd/WSNFSE.'
            : 'http://www.tinus.com.br/WSNFSE.';
        $method = $this->mapTinusMethod($service);
        return $base . $method . '.' . $method;
    }
}
