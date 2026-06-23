<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderHorusTrait
{
    /**
     * Envelopes Horus com alias por cidade e payload em tag xml.
     *
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildHorusEnvelope(array|string $payload, string $service, array $params, int $tpAmb): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapHorusMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $namespace = $this->buildHorusNamespace($params, $tpAmb);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="' . $this->xmlAttr($namespace) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<ser:' . $method . '>'
            . '<xml>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</xml>'
            . '</ser:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildHorusSoapAction(string $service, array $params, int $tpAmb): string
    {
        $base = $this->buildHorusNamespace($params, $tpAmb);
        return $base . '#' . $this->mapHorusMethod($service);
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildHorusNamespace(array $params, int $tpAmb): string
    {
        if ($tpAmb !== 1) {
            return 'http://teste.horusdm.com.br/service?wsdl';
        }

        $alias = trim((string) ($params['AliasCidade'] ?? $params['alias_cidade'] ?? $params['aliasCidade'] ?? ''));
        if ($alias === '') {
            throw new RuntimeException('Horus requer parametro AliasCidade no cadastro do provedor para ambiente de producao.');
        }
        return 'http://' . $alias . '.horusdm.com.br/service?wsdl';
    }

    private function mapHorusMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'consultar_lote' => 'ConsultarLoteRpsEnvio',
            'consultar_situacao' => 'ConsultarSituacaoLoteRpsEnvio',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfseRpsEnvio',
            'consultar_nfse', 'consultar_nf_se' => 'ConsultarNfseEnvio',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfseEnvio',
            'substituir_nfse', 'substituir_nf_se' => 'SubstituirNfseEnvio',
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'EnviarLoteRpsEnvio',
            default => 'EnviarLoteRpsEnvio',
        };
    }
}
