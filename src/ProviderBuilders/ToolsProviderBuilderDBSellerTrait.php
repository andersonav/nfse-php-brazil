<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderDBSellerTrait
{
    /**
     * @param array|string $payload
     */
    private function buildDBSellerEnvelope(array|string $payload, string $service, string $url, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $namespace = $this->dbSellerNamespace($url, $versao);
        $method = $this->mapDBSellerMethod($normalized);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:e="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<e:' . $method . '><xml>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</xml></e:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapDBSellerMethod(string $service): string
    {
        return match ($service) {
            'recepcionar_sincrono' => 'RecepcionarLoteRpsSincrono',
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'GerarNfse',
            'consultar_lote' => 'ConsultarLoteRps',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'ConsultarNfsePorFaixa',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfsePorRps',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'ConsultarNfseServicoPrestado',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'ConsultarNfseServicoTomado',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfse',
            'substituir_nfse', 'substituir_nf_se' => 'SubstituirNfse',
            default => 'RecepcionarLoteRps',
        };
    }

    private function buildDBSellerSoapAction(string $service, ?string $versao): string
    {
        if (str_starts_with((string) $versao, '2.')) {
            return '';
        }
        return 'http://www.dbseller.com.br/' . $this->mapDBSellerMethod(strtolower(trim($service)));
    }

    private function dbSellerNamespace(string $url, ?string $versao): string
    {
        if (str_starts_with((string) $versao, '2.')) {
            $base = rtrim((string) parse_url($url, PHP_URL_SCHEME) . '://' . (string) parse_url($url, PHP_URL_HOST), '/');
            if ($base !== '://') {
                return $base;
            }
        }
        return 'http://www.dbseller.com.br';
    }
}
