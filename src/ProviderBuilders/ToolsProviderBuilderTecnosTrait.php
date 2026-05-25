<?php

namespace Alves\NfseBrasil\ProviderBuilders;

use RuntimeException;

trait ToolsProviderBuilderTecnosTrait
{
    /**
     * @param array|string $payload
     */
    private function buildTecnosEnvelope(array|string $payload, string $service): string
    {
        $data = $this->normalizePayload($payload);
        $method = $this->mapTecnosMethod($service);
        $dados = trim((string) ($data['dados_xml'] ?? ''));
        if ($dados === '') {
            $dados = $this->buildAbrasfDataForMethod($data, $service);
        }

        $body = '<' . $method . ' xmlns="http://tempuri.org/">'
            . '<remessa>' . htmlspecialchars($dados, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</remessa>'
            . '</' . $method . '>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>' . $body . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function mapTecnosMethod(string $service): string
    {
        $normalized = strtolower(trim($service));
        return match ($normalized) {
            'recepcionar_sincrono', 'gerar_nfse', 'gerar_nf_se', 'emitir_nfse' => 'mEnvioLoteRPSSincronoComRetornoLista',
            'consultar_lote' => 'mConsultaLoteRPS',
            'consultar_nfse_faixa', 'consultar_nf_se_faixa' => 'mConsultaNFSePorFaixa',
            'consultar_nfse_rps', 'consultar_nf_se_rps' => 'mConsultaNFSePorRPS',
            'consultar_nfse_servico_prestado', 'consultar_nf_se_servico_prestado' => 'mConsultaNFSeServicosPrestados',
            'consultar_nfse_servico_tomado', 'consultar_nf_se_servico_tomado' => 'mConsultaNFSeServicosTomadosIntermediados',
            'cancelar_nfse', 'cancelar_nf_se' => 'mCancelamentoNFSe',
            default => 'mRecepcaoLoteRPS',
        };
    }

    private function buildTecnosSoapAction(string $service): string
    {
        return 'http://tempuri.org/' . $this->mapTecnosMethod($service);
    }
}
