<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderIiBrasilTrait
{
    /**
     * Envelopes iiBrasil (ABRASF 2.04 com tags *Request e nfseCabecMsg/nfseDadosMsg).
     *
     * @param array|string $payload
     */
    private function buildIiBrasilEnvelope(array|string $payload, string $service, ?string $versao): string
    {
        $data = $this->normalizePayload($payload);
        [$requestTag, $soapMethod] = $this->mapIiBrasilMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }
        $cabecalho = $this->buildAbrasfCabecalhoXml((string) ($data['versao_dados'] ?? ($versao ?: '2.04')));

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfse:' . $requestTag . '>'
            . '<nfseCabecMsg>' . htmlspecialchars($cabecalho, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseCabecMsg>'
            . '<nfseDadosMsg>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfseDadosMsg>'
            . '</nfse:' . $requestTag . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildIiBrasilSoapAction(string $service): string
    {
        [, $soapMethod] = $this->mapIiBrasilMethod($service);
        return 'http://nfse.abrasf.org.br/' . $soapMethod;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapIiBrasilMethod(string $service): array
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono' => ['RecepcionarLoteRpsSincronoRequest', 'RecepcionarLoteRpsSincrono'],
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['GerarNfseRequest', 'GerarNfse'],
            'consultar_lote' => ['ConsultarLoteRpsRequest', 'ConsultarLoteRps'],
            'consultar_nfse_faixa', 'consultar_nf_se_faixa', 'consultar_nfse', 'consultar_nf_se' => ['ConsultarNfsePorFaixaRequest', 'ConsultarNfsePorFaixa'],
            'consultar_nfse_rps', 'consultar_nf_se_rps' => ['ConsultarNfsePorRpsRequest', 'ConsultarNfsePorRps'],
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => ['ConsultarNfseServicoPrestadoRequest', 'ConsultarNfseServicoPrestado'],
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => ['ConsultarNfseServicoTomadoRequest', 'ConsultarNfseServicoTomado'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelarNfseRequest', 'CancelarNfse'],
            'substituir_nfse', 'substituir_nf_se' => ['SubstituirNfseRequest', 'SubstituirNfse'],
            default => ['RecepcionarLoteRpsRequest', 'RecepcionarLoteRps'],
        };
    }
}
