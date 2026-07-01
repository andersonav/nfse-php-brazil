<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderNEAInformaticaTrait
{
    /**
     * Envelopes NEAInformatica (método ABRASF com payload em CDATA no nó de envio).
     *
     * @param array|string $payload
     */
    private function buildNEAInformaticaEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        [$method, $innerTag] = $this->mapNEAInformaticaMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfse:' . $method . '>'
            . '<' . $innerTag . '><![CDATA[' . $dados . ']]></' . $innerTag . '>'
            . '</nfse:' . $method . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapNEAInformaticaMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => ['RecepcionarLoteRpsSincrono', 'EnviarLoteRpsSincronoEnvio'],
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GerarNfse', 'GerarNfseEnvio'],
            'consultar_lote' => ['ConsultarLoteRps', 'ConsultarLoteRpsEnvio'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa', 'consultar_nfse', 'consultar_nf_se' => ['ConsultarNfsePorFaixa', 'ConsultarNfsePorFaixaEnvio'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRps', 'ConsultarNfsePorRpsEnvio'],
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => ['ConsultarNfseServicoPrestado', 'ConsultarNfseServicoPrestadoEnvio'],
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => ['ConsultarNfseServicoTomado', 'ConsultarNfseServicoTomadoEnvio'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse', 'CancelarNfseEnvio'],
            'substituir_nfse', 'substituir_nf_se' => ['SubstituirNfse', 'SubstituirNfseEnvio'],
            default => ['RecepcionarLoteRps', 'EnviarLoteRpsEnvio'],
        };
    }
}
