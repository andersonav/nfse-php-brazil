<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderISSSaoPauloTrait
{
    /**
     * @param array|string $payload
     */
    private function buildISSSaoPauloEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $normalized = strtolower(trim($service));
        $schemaVersion = trim((string) ($data['versao_schema'] ?? '1'));
        $mensagem = trim((string) ($data['mensagem_xml'] ?? ''));
        if ($mensagem === '') {
            $mensagem = str_contains($normalized, 'cancelar')
                ? $this->buildAbrasfCancelarNfseEnvioXml($data)
                : $this->buildAbrasfEnviarLoteRpsEnvioXml($data);
        }

        [$requestTag] = $this->issSaoPauloServiceMap($normalized);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfe="http://www.prefeitura.sp.gov.br/nfe">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<nfe:' . $requestTag . '>'
            . '<nfe:VersaoSchema>' . $this->xmlValue($schemaVersion) . '</nfe:VersaoSchema>'
            . '<nfe:MensagemXML>' . htmlspecialchars($mensagem, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</nfe:MensagemXML>'
            . '</nfe:' . $requestTag . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function buildISSSaoPauloSoapAction(string $service): string
    {
        [, $soapAction] = $this->issSaoPauloServiceMap(strtolower(trim($service)));
        return $soapAction;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function issSaoPauloServiceMap(string $service): array
    {
        return match ($service) {
            'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => ['EnvioRPSRequest', 'http://www.prefeitura.sp.gov.br/nfe/ws/envioRPS'],
            'cancelar_nfse', 'cancelar_nf_se' => ['CancelamentoNFeRequest', 'http://www.prefeitura.sp.gov.br/nfe/ws/cancelamentoNFe'],
            'consultar_lote' => ['ConsultaLoteRequest', 'http://www.prefeitura.sp.gov.br/nfe/ws/consultaLote'],
            'consultar_nfse_rps', 'consultar_nf_se_rps', 'consultar_nfse', 'consultar_nf_se' => ['ConsultaNFeRequest', 'http://www.prefeitura.sp.gov.br/nfe/ws/consultaNFe'],
            'consultar_nfse_servico_prestado' => ['ConsultaNFeEmitidasRequest', 'http://www.prefeitura.sp.gov.br/nfe/ws/consultaNFeEmitidas'],
            'consultar_nfse_servico_tomado' => ['ConsultaNFeRecebidasRequest', 'http://www.prefeitura.sp.gov.br/nfe/ws/consultaNFeRecebidas'],
            default => ['EnvioLoteRPSRequest', 'http://www.prefeitura.sp.gov.br/nfe/ws/envioLoteRPS'],
        };
    }
}
