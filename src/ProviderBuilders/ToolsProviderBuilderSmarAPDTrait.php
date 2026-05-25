<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderSmarAPDTrait
{
    /**
     * @param array|string $payload
     * @param array<string,mixed> $params
     */
    private function buildSmarAPDEnvelope(array|string $payload, string $service, ?string $versao, array $params): string
    {
        $data = $this->normalizePayload($payload);
        $subVersao = (int) ($params['subversao'] ?? $params['SubVersao'] ?? 0);
        $isLegacy = $subVersao === 1;
        $normalized = strtolower(trim($service));
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        if ($isLegacy) {
            $method = match ($normalized) {
                'recepcionar_sincrono' => 'recepcionarLoteRpsSincrono',
                'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'gerarNfse',
                'consultar_lote' => 'consultarLoteRps',
                'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'consultarNfsePorFaixa',
                'consultar_nfse_rps', 'consultar_nf_se_rps' => 'consultarNfsePorRps',
                'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'consultarNfseServicoPrestado',
                'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'consultarNfseServicoTomado',
                'cancelar_nfse', 'cancelar_nf_se' => 'cancelarNfse',
                'substituir_nfse', 'substituir_nf_se' => 'substituirNfse',
                default => 'recepcionarLoteRps',
            };
            return '<?xml version="1.0" encoding="UTF-8"?>'
                . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">'
                . '<soapenv:Header/>'
                . '<soapenv:Body>'
                . '<nfse:' . $method . '><xml>' . $this->asCdata($dados) . '</xml></nfse:' . $method . '>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
        }

        return $this->buildGovDigitalEnvelope($data, $service, $versao ?: '2.04');
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildSmarAPDSoapAction(string $service, ?string $versao, array $params): string
    {
        $subVersao = (int) ($params['subversao'] ?? $params['SubVersao'] ?? 0);
        if ($subVersao === 1) {
            return '';
        }
        return $this->buildGovDigitalSoapAction($service);
    }
}
