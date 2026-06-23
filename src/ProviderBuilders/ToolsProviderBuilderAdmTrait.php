<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderAdmTrait
{
    /**
     * @param array|string $payload
     */
    private function buildAdmEnvelope(array|string $payload, string $service, string $url): string
    {
        $data = $this->normalizePayload($payload);
        [$method, $requestTag] = $this->mapAdmMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? '2.01'));
        $namespace = $this->admNamespace($data, $url);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfse:' . $requestTag . ' xmlns:nfse="' . $this->xmlAttr($namespace) . '">'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</nfse:' . $requestTag . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapAdmMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => ['RecepcionarLoteRpsSincrono', 'RecepcionarLoteRpsSincronoRequest'],
            'consultar_lote' => ['ConsultarLoteRps', 'ConsultarLoteRpsRequest'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfseRps', 'ConsultarNfseRpsRequest'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => ['ConsultarNfsePorFaixa', 'ConsultarNfsePorFaixaRequest'],
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => ['ConsultarNfseServicoPrestado', 'ConsultarNfseServicoPrestadoRequest'],
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => ['ConsultarNfseServicoTomado', 'ConsultarNfseServicoTomadoRequest'],
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GerarNfse', 'GerarNfseRequest'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse', 'CancelarNfseRequest'],
            'substituir_nfse', 'substituir_nf_se' => ['SubstituirNfse', 'SubstituirNfseRequest'],
            default => ['RecepcionarLoteRps', 'RecepcionarLoteRpsRequest'],
        };
    }

    /**
     * @param array<string,mixed> $data
     */
    private function admNamespace(array $data, string $url): string
    {
        $providerExtras = is_array($data['provider_extras'] ?? null) ? $data['provider_extras'] : [];
        $ns = trim((string) ($providerExtras['namespace'] ?? ''));
        if ($ns !== '') {
            return $ns;
        }
        return rtrim(strtok($url, '?') ?: $url, '/');
    }
}
