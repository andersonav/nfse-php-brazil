<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderIpm204Trait
{
    /**
     * @param array<string,mixed> $data
     */
    private function buildIpm204Envelope(array $data, string $service): string
    {
        [$methodTag, $innerTag] = match ($service) {
            'recepcionar_sincrono' => ['EnviarLoteRpsSincronoEnvio', 'EnviarLoteRpsSincronoEnvio'],
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GerarNfseEnvio', 'GerarNfseEnvio'],
            'consultar_lote' => ['ConsultarLoteRpsEnvio', 'ConsultarLoteRpsEnvio'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfseRpsEnvio', 'ConsultarNfseRpsEnvio'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => ['ConsultarNfseFaixaEnvio', 'ConsultarNfseFaixaEnvio'],
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => ['ConsultarNfseServicoPrestadoEnvio', 'ConsultarNfseServicoPrestadoEnvio'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfseEnvio', 'CancelarNfseEnvio'],
            'substituir_nfse', 'substituir_nf_se' => ['SubstituirNfseEnvio', 'SubstituirNfseEnvio'],
            default => ['EnviarLoteRpsEnvio', 'EnviarLoteRpsEnvio'],
        };
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $innerTag);
        }
        $dados = $this->extractInnerXmlByTagName($dados, $innerTag);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:net="net.atende">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<net:' . $methodTag . '>' . $dados . '</net:' . $methodTag . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildIpm204SoapAction(string $service): string
    {
        $map = [
            'recepcionar' => 'EnviarLoteRpsEnvio',
            'recepcionar_sincrono' => 'EnviarLoteRpsSincronoEnvio',
            'gerar_nfse' => 'GerarNfseEnvio',
            'gerar_nf_se' => 'GerarNfseEnvio',
            'emitir_nfse' => 'GerarNfseEnvio',
            'consultar_lote' => 'ConsultarLoteRpsEnvio',
            'consultar_nfse_rps' => 'ConsultarNfseRpsEnvio',
            'consultar_nf_se_rps' => 'ConsultarNfseRpsEnvio',
            'consultar_nfse_faixa' => 'ConsultarNfseFaixaEnvio',
            'consultar_nf_se_faixa' => 'ConsultarNfseFaixaEnvio',
            'consultar_nfse_servico_prestado' => 'ConsultarNfseServicoPrestadoEnvio',
            'consultar_nf_se_servico_prestado' => 'ConsultarNfseServicoPrestadoEnvio',
            'cancelar_nfse' => 'CancelarNfseEnvio',
            'cancelar_nf_se' => 'CancelarNfseEnvio',
            'substituir_nfse' => 'SubstituirNfseEnvio',
            'substituir_nf_se' => 'SubstituirNfseEnvio',
        ];
        $method = $map[$service] ?? 'EnviarLoteRpsEnvio';
        return 'net.atende#' . $method;
    }
}
