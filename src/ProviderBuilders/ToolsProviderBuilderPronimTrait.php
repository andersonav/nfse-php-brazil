<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderPronimTrait
{
    /**
     * @param array|string $payload
     */
    private function buildPronimEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        [$method] = $this->mapPronimMethod($service, $versao);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = trim((string) ($data['cabecalho_xml'] ?? ''));
        if ($cabecalho === '') {
            $vDados = str_starts_with((string) $versao, '2.03') ? '2.03' : '2.02';
            $vAtrib = str_starts_with((string) $versao, '2.03') ? '203' : '202';
            $cabecalho = '<tem:cabecalho versao="' . $this->xmlAttr($vAtrib) . '"><tem:versaoDados>' . $this->xmlValue($vDados) . '</tem:versaoDados></tem:cabecalho>';
        }

        $body = '<tem:' . $method . '>'
            . '<tem:xmlEnvio>' . $this->asCdata($dados) . '</tem:xmlEnvio>'
            . '</tem:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">'
            . '<soapenv:Header>' . $cabecalho . '</soapenv:Header>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapPronimMethod(string $service, ?string $versao): array
    {
        $normalized = strtolower(trim($service));
        $is203 = str_starts_with((string) $versao, '2.03');
        return match ($normalized) {
            'recepcionar_sincrono' => ['EnviarLoteRpsSincrono', 'INFSEGeracao/EnviarLoteRpsSincrono'],
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GerarNfse', 'INFSEGeracao/GerarNfse'],
            'consultar_lote' => ['ConsultarLoteRps', 'INFSEConsultas/ConsultarLoteRps'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => ['ConsultarNfsePorFaixa', 'INFSEConsultas/ConsultarNfsePorFaixa'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRps', 'INFSEConsultas/ConsultarNfsePorRps'],
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => ['ConsultarNfseServicoPrestado', 'INFSEConsultas/ConsultarNfseServicoPrestado'],
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => ['ConsultarNfseServicoTomado', 'INFSEConsultas/ConsultarNfseServicoTomado'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse', 'INFSEGeracao/CancelarNfse'],
            'substituir_nfse', 'substituir_nf_se' => ['SubstituirNfse', 'INFSEGeracao/SubstituirNfse'],
            'consultar_situacao' => ['ConsultarSituacaoLoteRps', 'INFSEConsultas/ConsultarSituacaoLoteRps'],
            'consultar_nfse', 'consultar_nf_se' => ['ConsultarNfse', 'INFSEConsultas/ConsultarNfse'],
            default => ['RecepcionarLoteRps', $is203 ? 'INFSEGeracao/RecepcionarLoteRps' : 'INFSEGeracao/RecepcionarLoteRps'],
        };
    }

    private function buildPronimSoapAction(string $service): string
    {
        [, $path] = $this->mapPronimMethod($service, null);
        return 'http://tempuri.org/' . $path;
    }
}
