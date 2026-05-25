<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderEloTechTrait
{
    /**
     * @param array|string $payload
     */
    private function buildEloTechEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapEloTechMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $method);
        }

        $body = '<nfse:' . $method . ' xmlns:nfse="http://shad.elotech.com.br/schemas/iss/nfse_v2_03.xsd">'
            . $dados
            . '</nfse:' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapEloTechMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'GerarNfseEnvio',
            'recepcionar_sincrono' => 'EnviarLoteRpsSincronoEnvio',
            'consultar_lote' => 'ConsultarLoteRpsEnvio',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'ConsultarNfseRpsEnvio',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'ConsultarNfseFaixaEnvio',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'ConsultarNfseServicoPrestadoEnvio',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'ConsultarNfseServicoTomadoEnvio',
            'cancelar_nfse', 'cancelar_nf_se' => 'CancelarNfseEnvio',
            'substituir_nfse', 'substituir_nf_se' => 'SubstituirNfseEnvio',
            default => 'EnviarLoteRpsEnvio',
        };
    }
}
