<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderFuturizeTrait
{
    /**
     * @param array|string $payload
     */
    private function buildFuturizeEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        [, $methodRequest] = $this->mapFuturizeMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml($versao ?: '2.02');

        $body = '<nfse:' . $methodRequest . '>'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</nfse:' . $methodRequest . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapFuturizeMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GerarNfse', 'GerarNfseRequest'],
            'recepcionar_sincrono' => ['RecepcionarLoteRpsSincrono', 'RecepcionarLoteRpsSincronoRequest'],
            'consultar_lote' => ['ConsultarLoteRps', 'ConsultarLoteRpsRequest'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRps', 'ConsultarNfsePorRpsRequest'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => ['ConsultarNfsePorFaixa', 'ConsultarNfsePorFaixaRequest'],
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => ['ConsultarNfseServicoPrestado', 'ConsultarNfseServicoPrestadoRequest'],
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => ['ConsultarNfseServicoTomado', 'ConsultarNfseServicoTomadoRequest'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfse', 'CancelarNfseRequest'],
            'substituir_nfse', 'substituir_nf_se' => ['SubstituirNfse', 'SubstituirNfseRequest'],
            default => ['RecepcionarLoteRps', 'RecepcionarLoteRpsRequest'],
        };
    }

    private function buildFuturizeSoapAction(string $service): string
    {
        [$method] = $this->mapFuturizeMethod($service);
        return 'http://nfse.abrasf.org.br/' . $method;
    }
}
