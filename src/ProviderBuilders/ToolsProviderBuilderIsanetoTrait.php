<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderIsanetoTrait
{
    /**
     * Envelopes Isaneto (ABRASF 2.03 com tags *Request e conteúdo interno direto).
     *
     * @param array|string $payload
     */
    private function buildIsanetoEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        [$requestTag, $innerTag] = $this->mapIsanetoMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $innerXml = $this->extractInnerXmlByTagName($dados, $innerTag);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfse:' . $requestTag . ' xmlns="http://www.abrasf.org.br/nfse.xsd" xmlns:ns2="http://www.w3.org/2000/09/xmldsig#">'
            . $innerXml
            . '</nfse:' . $requestTag . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapIsanetoMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => ['RecepcionarLoteRpsSincronoRequest', 'EnviarLoteRpsSincronoEnvio'],
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GerarNfseRequest', 'GerarNfseEnvio'],
            'consultar_lote' => ['ConsultarLoteRpsRequest', 'ConsultarLoteRpsEnvio'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa', 'consultar_nfse', 'consultar_nf_se' => ['ConsultarNfsePorFaixaRequest', 'ConsultarNfseFaixaEnvio'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRpsRequest', 'ConsultarNfseRpsEnvio'],
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => ['ConsultarNfseServicoPrestadoRequest', 'ConsultarNfseServicoPrestadoEnvio'],
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => ['ConsultarNfseServicoTomadoRequest', 'ConsultarNfseServicoTomadoEnvio'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfseRequest', 'CancelarNfseEnvio'],
            'substituir_nfse', 'substituir_nf_se' => ['SubstituirNfseRequest', 'SubstituirNfseEnvio'],
            default => ['RecepcionarLoteRpsRequest', 'EnviarLoteRpsEnvio'],
        };
    }
}
